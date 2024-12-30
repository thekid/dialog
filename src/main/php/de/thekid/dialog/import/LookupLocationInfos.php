<?php namespace de\thekid\dialog\import;

use lang\FormatException;
use util\URI;
use webservices\rest\Endpoint;

/** Aggregate coordinates from Google Maps links */
class LookupLocationInfos extends Task {

  public function __construct(private array $entry) { }

  public function execute(Endpoint $api) {
    $endpoints= [];
    foreach ($this->entry['locations'] as &$location) {

      // Support both https://maps.app.goo.gl/[app-id] and https://goo.gl/maps/[maps-id].
      // Both redirect to the full maPS URL, their IDs are however not interchangeable!
      $uri= new URI($location['link']);
      $endpoints[$uri->host()]??= new Endpoint($uri);

      $r= $endpoints[$uri->host()]->resource(new URI($location['link'])->path())->get();
      if (preg_match('#/maps/place/[^/]+/@([0-9.-]+),([0-9.-]+),([0-9.]+)z#', $r->header('Location'), $m)) {
        $location['lat']= (float)$m[1];
        $location['lon']= (float)$m[2];
        $location['zoom']= (float)$m[3];
      } else if (preg_match('#/maps/search/([0-9.-]+),.([0-9.-]+)#', $r->header('Location'), $m)) {
        $location['lat']= (float)$m[1];
        $location['lon']= (float)$m[2];
        $location['zoom']= 1.0;
      } else {
        throw new FormatException('Cannot resolve '.$location['link'].': '.$r->toString());
      }

      yield $location['name'] => $location['lat'].','.$location['lon'];
    }
    return $this->entry['locations'];
  }

  public function description(): string { return 'Looking up location infos'; }
}