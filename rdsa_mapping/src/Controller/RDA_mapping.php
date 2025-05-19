<?php
namespace Drupal\rdasa_mapping\Controller;
use Drupal\Core\Controller\ControllerBase;
use Geocoder\Model\Coordinates;
use Symfony\Component\HttpFoundation\Request;

class RDA_mapping extends ControllerBase {

  /**
   * Address String to location array
   * sReturn @array
   */
  public function addressStringToLocation(array $Address): Coordinates {
    $provider_id = "mapbox";
    $provider = \Drupal::entityTypeManager()
      ->getStorage('geocoder_provider')
      ->load($provider_id);
    $address_service = \Drupal::service("geocoder_address.address");
    $formatter = \Drupal::service('plugin.manager.geocoder.formatter');
    $Address = $address_service->addressArrayToGeoString($Address);
    $result = $provider->getPlugin()->geocode($Address);
    $first_result = $result->first();
    $coordinates = $first_result->getCoordinates();
    return $coordinates;
  }

}
