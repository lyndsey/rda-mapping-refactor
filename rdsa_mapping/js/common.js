function renderMap($, settings) {
  var accessToken = settings.accessToken;
  var finalRender = settings.options.finalRender;
  var container   = settings.position.instance_delta;
  var keyboard    = settings.options.keyboard;
  var maxPitch    = settings.options.maxPitch != 0 ? settings.options.maxPitch : 65;
  var minZoom     = settings.options.minZoom;
  var maxZoom     = settings.options.maxZoom;
  var threeD      = settings.options.threeD;
  var marked      = settings.options.marked;
  var marker      = settings.position;
  var pitch       = settings.options.pitch != 0 ? settings.options.pitch : 45;
  var zoom        = settings.options.zoom ? settings.options.zoom : 9;
  var lat         = marker.lat ? marker.lat : 40.73119569710681;
  var lng         = marker.lng ? marker.lng : -73.98930566093547;
  var instanceId  = 'mapbox-'+container.split('-')[1];
  if(accessToken != null) {
    mapboxgl.accessToken = accessToken;
    var map = new mapboxgl.Map({
      container: container,
      style: settings.style,
      center: [lng, lat],
      zoom: zoom,
      maxZoom: maxZoom,
      minZoom: minZoom,
      pitch: pitch,
      bearing: -17.6,
      maxPitch: maxPitch,
      keyboard: keyboard
    });
    if (finalRender == undefined) {
      map.addControl(new mapboxgl.NavigationControl());
      map.on('click', function(e){
        var lat = e.lngLat.lat;
        var lng = e.lngLat.lng;
        $('input[data-lat-delta="'+ instanceId +'"]').val(lat);
        $('input[data-lng-delta="'+ instanceId +'"]').val(lng);
        $('input[data-marked-delta="'+ instanceId +'"]').val('1');
        var el = document.getElementById(marker.instance_marker);
        el.style.display = 'inline-block';

        new mapboxgl.Marker(el).setLngLat([lng, lat]).addTo(map);
      });
    }
    map.on('load', function() {
      var inputLng = $('input[data-lng-delta="'+container+'"]').val()
      var inputLat = $('input[data-lat-delta="'+container+'"]').val()
      var lng = marker.lng ? marker.lng : inputLng;
      var lat = marker.lat ? marker.lat : inputLat;
      var el = document.getElementById(marker.instance_marker);
      el.style.display = 'inline-block';
      if (marker.markerText)
        var popup = new mapboxgl.Popup({ offset: 40 }).setText(marker.markerText);

      el.style.backgroundImage = 'url("'+marker.marker+'")';
      new mapboxgl.Marker(el).setLngLat([lng, lat]).setPopup(popup).addTo(map);
      $('input[data-3d-delta="'+container+'"]').change(function(){
        if (this.checked) {
          threeDLayer(map);
        } else {
          map.removeLayer('3d-buildings')
        }
      });
      map.resize();
      if (threeD == 1) {
        threeDLayer(map);
      }
      $('select[data-maxZoom-delta="'+container+'"]').change(function(){
        map.setMaxZoom($(this).val());
      });
      $('select[data-minZoom-delta="'+container+'"]').change(function(){
        map.setMinZoom($(this).val());
      });
      $('select[data-maxPitch-delta="'+container+'"]').change(function(){
        map.setMaxPitch($(this).val());
      });
    });
    map.on('zoomend', function(){
      $('input[data-zoom-delta="'+container+'"]').val(map.getZoom());
    })
    return map;
  }
}

function threeDLayer(map) {
  var layers = map.getStyle().layers;
  var labelLayerId;
  for (var i = 0; i < layers.length; i++) {
    if (layers[i].type === 'symbol' && layers[i].layout['text-field']) {
      labelLayerId = layers[i].id;
      break;
    }
  }
  map.addLayer({
    'id': '3d-buildings',
    'source': 'composite',
    'source-layer': 'building',
    'filter': ['==', 'extrude', 'true'],
    'type': 'fill-extrusion',
    'minzoom': 15,
    'paint': {
      'fill-extrusion-color': '#aaa',
      'fill-extrusion-height': [
      "interpolate", ["linear"], ["zoom"],
      15, 0,
      15.05, ["get", "height"]
      ],
      'fill-extrusion-base': [
      "interpolate", ["linear"], ["zoom"],
      15, 0,
      15.05, ["get", "min_height"]
      ],
      'fill-extrusion-opacity': .6
    }
  }, labelLayerId);
}
