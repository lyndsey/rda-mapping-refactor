mapboxgl.accessToken = 'pk.eyJ1IjoicmRhc2EiLCJhIjoiY2xrN2x5emNoMDhhZjNnbzQ2azE5cjBiZiJ9.GnFSisdnaSWxWWTjVPYjmA';

// Find a container by class or known ID
const container = document.querySelector('.mapbox-map') || document.getElementById('map');

if (container) {
  const map = new mapboxgl.Map({
    container: container,
    style: 'mapbox://styles/mapbox/streets-v12',
    center: [-91.874, 42.76],
    zoom: 14,
    pitch: 0,                 // No tilt
    bearing: 0,               // North facing
    antialias: true           // Smoother rendering
  });

// Add this line to show zoom and compass controls:
map.addControl(new mapboxgl.NavigationControl());

  console.log("here");
  console.log(map);

  const draw = new MapboxDraw({
    displayControlsDefault: false,
    controls: {
      polygon: true,
      trash: true
    },
    defaultMode: 'draw_polygon'
  });

  map.addControl(draw);

  map.on('draw.create', updateArea);
  map.on('draw.delete', updateArea);
  map.on('draw.update', updateArea);

  function updateArea(e) {
    const data = draw.getAll();
    const answer = document.getElementById('calculated-area');
    if (data.features.length > 0) {
      const area = turf.area(data);
      const rounded_area = Math.round(area * 100) / 100;
      answer.innerHTML = `<p><strong>${rounded_area}</strong></p><p>square meters</p>`;
    } else {
      answer.innerHTML = '';
      if (e.type !== 'draw.delete') {
        alert('Click the map to draw a polygon.');
      }
    }
  }
} else {
  console.warn('Map container not found.');
}