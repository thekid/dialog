const markers = {
  style   : new ol.style.Style({image: new ol.style.Icon(({src: '/static/marker.png'}))}),
  list    : [],
  add     : function(slug, lon, lat, name) {
    const marker = new ol.Feature({
      geometry : new ol.geom.Point(ol.proj.fromLonLat([lon, lat])),
      slug     : slug,
      name     : name
    });
    marker.setStyle(markers.style);
    markers.list.push(marker);
  },
  project : function($element) {
    const source = new ol.source.Vector({features: markers.list});
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
        list += `<li><a href="#${feature.get('slug')}">${feature.get('name')}</a></li>`;
      })
      if (0 === list.length) return;

      $popup.innerHTML = '<ul>' + list + '</ul>';
      $popup.style.left = event.pixel[0] + 'px';
      $popup.style.top = event.pixel[1] + 'px';
      $popup.style.display = 'block';
    });
  }
};
