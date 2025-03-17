<?php namespace de\thekid\dialog;

use lang\IllegalArgumentException;
use util\{Date, TimeZone, URI};
use webservices\rest\Endpoint;

/**
 * Open-Meteo is an open-source weather API and offers free access for non-commercial use.
 *
 * @see https://github.com/open-meteo/open-meteo
 */
class OpenMeteo {
  private const URLENCODED= 'application/x-www-form-urlencoded';
  private $base;
  private $auth= [];
  private $endpoints= [];

  public function __construct(string|URI $base) {
    $this->base= $base instanceof URI ? $base : new URI($base);
  }

  /** Returns a given API endpoint */
  protected function endpoint(string $kind): Endpoint {
    return $this->endpoints[$kind]??= new Endpoint($this->base->using()
      ->host($kind.'.'.$this->base->host())
      ->create()
    );
  }

  public function lookup(string|float $lat, string|float $lon, Date $start, ?Date $end= null, ?TimeZone $tz= null): array<string, mixed> {
    $params= $this->auth + [
      'latitude'   => $lat,
      'longitude'  => $lon,
      'start_date' => $start->toString('Y-m-d'),
      'end_date'   => ($end ?? $start)->toString('Y-m-d'),
      'timezone'   => ($tz ?? $start->getTimeZone())->name(),
      'daily'      => ['sunrise', 'sunset'],
      'hourly'     => ['weather_code', 'apparent_temperature'],
    ];

    // There's a 5-day delay in the historical data; forecasts are provided for 7 days
    if ($start->getTime() >= time() - 604800) {
      $resource= $this->endpoint('api')->resource('forecast');
    } else {
      $resource= $this->endpoint('archive-api')->resource('archive');
    }

    return $resource->post($params, self::URLENCODED)->match([
      200 => fn($r) => $r->value(),
      400 => fn($r) => throw new IllegalArgumentException($r->content()),
    ]);
  }
}