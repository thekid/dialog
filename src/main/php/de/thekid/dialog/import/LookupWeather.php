<?php namespace de\thekid\dialog\import;

use de\thekid\dialog\OpenMeteo;
use lang\FormatException;
use util\{Date, Dates, TimeZone, TimeInterval};
use webservices\rest\Endpoint;

/**
 * Aggregates weather for entries using OpenMeteo
 *
 * @test  de.thekid.dialog.unittest.LookupWeatherTest
 */
class LookupWeather extends Task {

  public function __construct(
    private array<string, mixed> $entry,
    private OpenMeteo $weather= new OpenMeteo('https://open-meteo.com/v1'),
  ) { }

  public function execute(Endpoint $api) {
    if (empty($this->entry['images'])) {
      yield 'skip' => 'no images';
      return null;
    }

    $weather= [];
    $min= $max= null;
    $hourly= fn($date) => Dates::truncate($date, TimeInterval::$HOURS)->toString('Y-m-d\TH:i');
    foreach ($this->entry['locations'] as $location) {
      $tz= new TimeZone($location['timezone']);

      // Infer date range from first and last images
      $dates= [];
      foreach ($this->entry['images'] as $image) {
        $dates[]= new Date($image['meta']['dateTime'], $tz);
      }
      usort($dates, fn($a, $b) => $b->compareTo($a));
      $first= current($dates);
      $last= end($dates);

      // Filter hourly weather for the duration of the images
      $result= $this->weather->lookup($location['lat'], $location['lon'], $first, $last, $tz);
      $offset= array_search($first |> $hourly, $result['hourly']['time']);
      $length= array_search($last |> $hourly, $result['hourly']['time']) - $offset + 1;

      // Determine most common weather codes and temperature range
      $codes= array_slice($result['hourly']['weather_code'], $offset, $length) |> array_count_values(...);
      $temp= array_slice($result['hourly']['apparent_temperature'], $offset, $length);
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