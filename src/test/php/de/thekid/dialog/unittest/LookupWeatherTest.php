<?php namespace de\thekid\dialog\unittest;

use de\thekid\dialog\OpenMeteo;
use de\thekid\dialog\import\LookupWeather;
use test\{Assert, Expect, Test, Values};
use util\{Date, TimeZone};
use webservices\rest\TestEndpoint;

class LookupWeatherTest {
  private const BERLIN= [
    'name'     => 'Berlin',
    'timezone' => 'Europe/Berlin',
    'lat'      => '52.520007',
    'lon'      => '13.404954',
  ];
  private const KARLSRUHE= [
    'name'     => 'Karlsruhe',
    'timezone' => 'Europe/Berlin',
    'lat'      => '49.0068901',
    'lon'      => '8.4036527',
  ];

  /** Executes the lookup for a given entry and returns its result */
  private function execute(array<string, mixed> $entry): mixed {
    $fixture= new LookupWeather($entry, new class('https://openmeteo.example.com/') extends OpenMeteo {
      public function lookup(string|float $lat, string|float $lon, Date $start, ?Date $end= null, ?TimeZone $tz= null): array<string, mixed> {
        return match ("{$lat}/{$lon}") {
          '52.520007/13.404954' => [
            'hourly' => [
              'time' => ['2025-05-29T08:00', '2025-05-29T09:00', '2025-05-29T10:00', '2025-05-29T11:00', '2025-05-29T12:00'],
              'weather_code' => ['02', '02', '02', '02', '03'],
              'apparent_temperature' => [9.7, 10.1, 11.3, 12.1, 13.3],
            ],
          ],
          '49.0068901/8.4036527' => [
            'hourly' => [
              'time' => ['2025-05-29T08:00', '2025-05-29T09:00', '2025-05-29T10:00', '2025-05-29T11:00', '2025-05-29T12:00'],
              'weather_code' => ['01', '01', '01', '01', '01'],
              'apparent_temperature' => [15.8, 16.2, 17.3, 17.3, 18.4],
            ],
          ],
        };
      }
    });
    $result= $fixture->execute(new TestEndpoint([]));

    foreach ($result as $_) { }
    return $result->getReturn();
  }

  #[Test]
  public function can_create() {
    new LookupWeather([]);
  }

  #[Test]
  public function without_images() {
    Assert::null($this->execute([
      'locations' => [self::BERLIN],
      'images'    => [],
    ]));
  }

  #[Test]
  public function min_and_max_temperature_for_single_image() {
    Assert::equals(['code' => '02', 'min' => 11.3, 'max' => 11.3], $this->execute([
      'locations' => [self::BERLIN],
      'images'    => [
        ['name' => 'IMG_0001.jpg', 'meta' => ['dateTime' => '2025-05-29 10:36:53+0100']],
      ],
    ]));
  }

  #[Test]
  public function most_common_weather_code_returned() {
    Assert::equals(['code' => '02', 'min' => 11.3, 'max' => 13.3], $this->execute([
      'locations' => [self::BERLIN],
      'images'    => [
        ['name' => 'IMG_0001.jpg', 'meta' => ['dateTime' => '2025-05-29 10:36:53+0100']],
        ['name' => 'IMG_0002.jpg', 'meta' => ['dateTime' => '2025-05-29 11:52:29+0100']],
        ['name' => 'IMG_0003.jpg', 'meta' => ['dateTime' => '2025-05-29 12:14:07+0100']],
      ],
    ]));
  }

  #[Test]
  public function multiple_locations() {
    Assert::equals(['code' => '01', 'min' => 11.3, 'max' => 18.4], $this->execute([
      'locations' => [self::BERLIN, self::KARLSRUHE],
      'images'    => [
        ['name' => 'IMG_0001.jpg', 'meta' => ['dateTime' => '2025-05-29 10:36:53+0100']],
        ['name' => 'IMG_0002.jpg', 'meta' => ['dateTime' => '2025-05-29 11:52:29+0100']],
        ['name' => 'IMG_0003.jpg', 'meta' => ['dateTime' => '2025-05-29 12:14:07+0100']],
      ],
    ]));
  }
}