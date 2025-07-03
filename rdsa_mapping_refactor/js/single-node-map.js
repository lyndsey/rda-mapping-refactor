/* No longer used. Logic moved to mapbox-field.html.twig for consistency.
console.log("Single node map JS loaded");
(function (Drupal, once) {
  Drupal.behaviors.singleNodeMap = {
    attach: function (context, settings) {
      once('singleNodeMapInit', '#single-node-map', context).forEach(function (mapElement) {
        if (!settings.mapbox) return;

        mapboxgl.accessToken = settings.mapbox.accessToken;

        const map = new mapboxgl.Map({
         container: 'single-node-map',
         style: 'mapbox://styles/mapbox/streets-v12',
         center: [settings.mapbox.position.lng, settings.mapbox.position.lat],
          zoom: 2,
          pitch: 0,      // remove tilt
         bearing: 0     // reset rotation
        });

        map.on('load', () => {
          const el = document.createElement('div');
          el.className = settings.mapbox.public === 'Yes' ? 'marker' : 'marker-red';

          const popup = new mapboxgl.Popup({ offset: 25 }).setHTML(
            `<p>${settings.mapbox.description}</p>`
          );

          new mapboxgl.Marker(el)
            .setLngLat([settings.mapbox.position.lng, settings.mapbox.position.lat])
            .setPopup(popup)
            .addTo(map);
        });

        // Handle style toggle buttons
        document.querySelectorAll('.map-style-toggle button').forEach(button => {
          button.addEventListener('click', function () {
            const styleId = this.getAttribute('data-style');
            map.setStyle(`mapbox://styles/mapbox/${styleId}`);
          });
        });
      });
    }
  };
})(Drupal, once);
*/