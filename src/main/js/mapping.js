class Mapping {
  #list = [];

  /** Creates new map using given marker images */
  constructor(image) {
    this.style = new ol.style.Style({image: new ol.style.Icon(({src: image}))})
  }

  /** Add marker and link at a given position */
  mark(link, lon, lat, name) {
    const marker = new ol.Feature({
      geometry : new ol.geom.Point(ol.proj.fromLonLat([lon, lat])),
      link     : link,
      name     : name
    });
    marker.setStyle(this.style);
    this.#list.push(marker);
  }

  /** Escape input for use in HTML */
  html(input) {
    return input.replace(/[<>&]/g, c => '&#' + c.charCodeAt(0) + ';');
  }

  /** Project this map on to a given DOM element */
  project($element) {
    const source = new ol.source.Vector({features: this.#list});
    const map = new ol.Map({
      target: $element,
      layers: [new ol.layer.Tile({source: new ol.source.OSM()})]
    });
    map.addLayer(new ol.layer.Vector({source: source}));
    map.getView().fit(source.getExtent(), {padding: [32, 32, 32, 32], minResolution: 30});

    const $popup = $element.querySelector('.popup');
    map.on('movestart', event => {
      $popup.style.display = 'none';
    });
    map.on('click', event => {
      $popup.style.display = 'none';
      let list = '';
      map.forEachFeatureAtPixel(event.pixel, feature => {
        const link = feature.get('link');
        if (null === link) {
          list += `<li>${this.html(feature.get('name'))}</li>`;
        } else {
          list += `<li><a href="${link}">${this.html(feature.get('name'))}</a></li>`;
        }
      })
      if (0 === list.length) return;

      $popup.innerHTML = '<ul>' + list + '</ul>';
      $popup.style.left = event.pixel[0] + 'px';
      $popup.style.top = event.pixel[1] + 'px';
      $popup.style.display = 'block';
    });
  }
}