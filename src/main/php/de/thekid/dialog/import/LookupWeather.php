<?php namespace de\thekid\dialog\import;

use de\thekid\dialog\OpenMeteo;
use lang\FormatException;
use util\{Date, Dates, TimeZone, TimeInterval};
use webservices\rest\Endpoint;

/** Aggregates weather for entries using OpenMeteo */
class LookupWeather extends Task {
  private $weather= new OpenMeteo('https://open-meteo.com/v1');

  public function __construct(private array<string, mixed> $entry, private array<mixed> $images) { }

  public function execute(Endpoint $api) {
    $weather= [];
    $min= $max= null;
    foreach ($this->entry['locations'] as $location) {
      $tz= new TimeZone($location['timezone']);

      // Infer date range from first and last images
      $dates= [];
      foreach ($this->images as $image) {
        $dates[]= new Date($image['meta']['dateTime'], $tz);
      }
      usort($dates, fn($a, $b) => $b->compareTo($a));
      $first= current($dates);
      $last= end($dates);

      // Filter hourly weather for the duration of the images
      $result= $this->weather->lookup($location['lat'], $location['lon'], $first, $last, $tz);
      $start= array_search(Dates::truncate($first, TimeInterval::$HOURS)->toString('Y-m-d\TH:i'), $result['hourly']['time']);
      $end= array_search(Dates::truncate($last, TimeInterval::$HOURS)->toString('Y-m-d\TH:i'), $result['hourly']['time']);

      // Determine most common weather codes and temperature range
      $codes= array_count_values(array_slice($result['hourly']['weather_code'], $start, 1 + ($end - $start)));
      $temp= array_slice($result['hourly']['apparent_temperature'], $start, 1 + ($end - $start));
      $min= null === $min ? min($temp) : min($min, min($temp));
      $max= null === $max ? max($temp) : max($max, max($temp));

      arsort($codes);
      foreach ($codes as $code => $count) {
        $weather[$code]??= 0;
        $weather[$code]+= $count;
      }

      yield $location['name'] => sprintf('#%02d @ %.1f-%.1f °C', key($codes), min($temp), max($temp));
    }

    arsort($weather);
    return [
      'code' => sprintf('%02d', key($weather)),
      'min'  => $min,
      'max'  => $max,
    ];
  }

  public function description(): string { return 'Looking up weather'; }
}