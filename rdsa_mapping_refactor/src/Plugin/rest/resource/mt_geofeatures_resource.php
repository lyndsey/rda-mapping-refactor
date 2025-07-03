<?php


namespace Drupal\mapping_tool\Plugin\rest\resource;

use Drupal\Component\Serialization\Json;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use http\Client\Request;


/**
 * Custom resource for the mt_geofeatures
 *
 * @RestResource(
 *   id = "mt_geofeatures_resource",
 *   label = @Translation("MT GeoFeatures Handler"),
 *   uri_paths = {
 *      "canonical" = "/endpoint/save_geofeatures",
 *      "https://www.drupal.org/link-relations/create" = "/endpoint/save_geofeatures"
 *   }
 * )
 */
class mt_geofeatures_resource extends ResourceBase {

  public function post(Request $request): void
  {
    // $geometry = Json::decode();
    $params = Json::decode($request->getContent());
    $tt = "ss";
  }

}
