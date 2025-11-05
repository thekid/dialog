class Mapping {
  #coords = [];
  #markers = [];
  #features = [];

  /** Add marker and link at a given position */
  mark(link, lon, lat, name, kind, uri) {
    const $image = document.createElement('img');
    $image.src = uri;
    $image.alt = name;
    $image.classList.add('marker');
    $image.classList.add(kind);

    const position = ol.proj.fromLonLat([lon, lat]);
    this.#coords.push(position);
    this.#markers.push(new ol.Overlay({
      position: position,
      positioning: 'center-center',
      element: $image,
      stopEvent: false
    }));
    this.#features.push(new ol.Feature({
      geometry: new ol.geom.Point(position),
      link: link,
      name: name
    }));
  }

  /** Escape input for use in HTML */
  html(input) {
    return input.replace(/[<>&]/g, c => '&#' + c.charCodeAt(0) + ';');
  }

  /** Project this map on to a given DOM element */
  project($element, connect) {
    const map = new ol.Map({
      target: $element,
      layers: [new ol.layer.Tile({source: new ol.source.OSM()})]
    });
    for (const overlay of this.#markers) {
      map.addOverlay(overlay);
    }

    const features = new ol.source.Vector({features: this.#features});

    // If there is more than one coordinate, connect with dotted lines
    if (connect && this.#coords.length > 1) {
      const line = new ol.Feature({geometry: new ol.geom.LineString(this.#coords)});
      line.setStyle(new ol.style.Style({
        stroke: new ol.style.Stroke({
          color: '#666666',
          width: 3,
          lineDash: [4, 6],
          lineCap: 'round'
        })
      }));
      features.addFeature(line);
    }

    map.addLayer(new ol.layer.Vector({source: features}));
    map.getView().fit(features.getExtent(), {padding: [32, 32, 32, 32], minResolution: 30});

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