<?php

namespace Drupal\rdasa_mapping\Plugin\Field\FieldFormatter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mapbox\Mapbox;

/**
 * Plugin implementation of the 'mapbox_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "mapbox_formatter_mt",
 *   label = @Translation("Mapbox - Mapping Tool"),
 *   field_types = {
 *     "mapbox_field"
 *   }
 * )
 */
class MapboxFormatter_MT extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\mapbox\Mapbox definition.
   *
   * @var \Drupal\mapbox\Mapbox
   */
  protected $mapbox;

  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, Mapbox $mapbox) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->mapbox = $mapbox;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('mapbox')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array
  {
    $accessToken  = $this->mapbox->accessToken();
    $style        = "mapbox://styles/rdasa/clkgnq0r7002j01pu3ygf4c08";

    $elements = [];

    foreach ($items as $delta => $item) {
      $instance_num   = 0;
      $instance_num   = $delta + 1;
      $instance_delta = 'mapbox-'.$items[$delta]->getEntity()->id().$delta.$instance_num;
      $instance_marker = 'mapbox-marker-'.$items[$delta]->getEntity()->id().$delta.$instance_num;

      $mapbox['accessToken'] = $accessToken;
      $mapbox['style']       = $style;
      $mapbox['options'] = [
        'finalRender' => TRUE,
        'maxPitch'    => (int) $items[$delta]->maxPitch,
        'keyboard'    => (int) $items[$delta]->keyboard,
        'minZoom'     => (int) $items[$delta]->minZoom,
        'maxZoom'     => (int) $items[$delta]->maxZoom,
        'threeD'      => (int) $items[$delta]->threeDBuildings,
        'marked'      => (int) $items[$delta]->marked,
        'pitch'       => (int) $items[$delta]->pitch,
        'zoom'        => (int) $items[$delta]->zoom,
      ];
      $mapbox_field_path = \Drupal::service('extension.path.resolver')->getPath('module', 'mapbox_field');
      $mapbox['position'] = [
        'marker'          => $items[$delta]->marker ? $items[$delta]->marker : '/'. $mapbox_field_path .'/marker.png',
        'markerText'      => $items[$delta]->markerText ?? $items[$delta]->markerText,
        'lat'             => (float) $items[$delta]->lat,
        'lng'             => (float) $items[$delta]->lng,
        'instance_delta'  => $instance_delta,
        'instance_marker' => $instance_marker
      ];

      // Get this Mapboxes GeoFeatures from the view
      $geofeatures_view = \Drupal\views\Views::getView('projects_map');
      $geofeatures_view->build('block_1');
      $geofeatures_view->execute();
      $geofeatures_view->postexecute();
      $results = $geofeatures_view->result;
      foreach($results as $result) {
        $geofeature = $result->_entity->field_project_location->getValue();
        $lat = (float)$geofeature[0]["lat"];
        $lng = (float)$geofeature[0]["lng"];
        $features[] = [
          "type" => "Feature",
          "properties" => ["description"=>"<strong>Make it Mount Pleasant</strong><p>Make it Mount Pleasant is a handmade and vintage market and afternoon of live entertainment and kids activities. 12:00-6:00 p.m.</p>",
                          "icon"=>"theatre"],
          "geometry" => ["coordinates" => [$lng, $lat], "type"=>"Point"]
        ];
      }
      $data = ["type" => "FeatureCollection", "features"=>$features];
      $geoFeatures = ["type"=>"geojson", "data" => $data];
      $mapbox["geo_json"] = $geoFeatures;
      /**
      {
        'type': 'geojson',
      'data': {
        "type": "FeatureCollection",
        "features": [
          {
            "type": "Feature",
            "properties": {},
            "geometry": {
            "coordinates": [
              [
                [
                  146.945613,
                  -36.186275
                ],
                [
                  146.945694,
                  -36.186221
                ],
                [
                  146.945737,
                  -36.186281
                ],
                [
                  146.945851,
                  -36.186213
                ],
                [
                  146.94595,
                  -36.18632
                ],
                [
                  146.945753,
                  -36.186428
                ],
                [
                  146.945613,
                  -36.186275
                ]
              ]
            ],
              "type": "Polygon"
            },
            "id": "3777bfc992176ba58fb35337fb42f79f"
          },
          {
            "type": "Feature",
            "properties": {},
            "geometry": {
            "coordinates": [
              [
                146.945744,
                -36.18597
              ],
              [
                146.946032,
                -36.186244
              ]
            ],
              "type": "LineString"
            },
            "id": "44c758414c3cbb8008826c79267a9978"
          },
          {
            "type": "Feature",
            "properties": {},
            "geometry": {
            "coordinates": [
              [
                146.945769,
                -36.185951
              ],
              [
                146.946075,
                -36.186229
              ]
            ],
              "type": "LineString"
            },
            "id": "4e54dfcff0935b6eca71c6eef9b6524c"
          },
          {
            "type": "Feature",
            "properties": {},
            "geometry": {
            "coordinates": [
              146.945715,
              -36.186061
            ],
              "type": "Point"
            },
            "id": "68fce9a66404ac76a02b9d64ca316c83"
          },
          {
            "type": "Feature",
            "properties": {},
            "geometry": {
            "coordinates": [
              146.945733,
              -36.186175
            ],
              "type": "Point"
            },
            "id": "d39eb73d29ff169281078d78ce1deb64"
          }
        ]
      }
    }
       **/
      $element['#theme']  = 'mapbox_field';
      $element['#mapbox'] = $mapbox;
      # $element['#attached']['library'][] = 'mapping_tool/apis';
      $element['#attached']['library'][] = 'mapbox/renderer';
      $element['#attached']['drupalSettings']['mapbox_renderer'][] = $mapbox;

       $elements[$delta] = $element;
    }

  return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
