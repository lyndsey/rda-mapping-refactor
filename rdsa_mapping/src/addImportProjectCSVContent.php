<?php
namespace Drupal\rdasa_mapping;

use CommerceGuys\Addressing\Address;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Geocoder\Provider\Mapbox\Model\MapboxAddress;


class addImportProjectCSVContent {
  public static function addImportProjectCSVContentItem($item, $form_values, &$context): void {
    if (empty($item)) {
      \Drupal::messenger()->addMessage("No Items");
      return;
    }
    $context['sandbox']['current_item'] = $item;
    $project_lead = "";
    if (!empty($item["Proponent or Project lead"])) {
      $project_lead =  $item["Proponent or Project lead"];
    }
    $business_name = "";
    $message = 'Creating ' . $item['Project Name'] . " by " . $project_lead . $business_name;
    $results = array();
    create_node($item, $form_values);
    $context['message'] = $message;
    $context['results'][] = $item;
  }
  function addImportProjectCSVContentItemCallback($success, $results, $operations): void {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One item processed.', '@count items processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    \Drupal::messenger()->addMessage($message);
    \Drupal::logger('rdasa_project_import')->error($message);

  }
}

// This function actually creates each item as a node as type 'Page'
/**
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function create_node($item, $form_values): void {
  // @@todo Get the ID here so i can put it in error messages.
  $node_data['type'] = 'project';
  $business_name = "";
  $proponent = "";
  if (!empty($item["Business Name"])) {
    $business_name = $item["Business Name"];
    $node_data['field_business_name'] = $business_name;
  }
  if (!empty($item["Proponent or Project lead"])) {
    $proponent = $item["Proponent or Project lead"];
    $node_data['field_proponent_or_project_lead'] = $proponent;
  }
  if (!empty($item["Project Name"])) {
    $node_data['title'] = $item['Project Name'];
  } else {
    $node_data["title"] = "Project by " . $proponent . " " . $business_name;
  }
  if (!empty($form_values["project_item_group"])) {
    $node_data["field_rda_area"] = $form_values["project_item_group"];
  }
  if(!empty($form_values["project_public"])) {
    $node_data["field_public_project"] = $form_values["project_public"];
  }
  if (!empty($item["Initial Contract Date"])) {
    $node_data['field_initial_contact'] = $item['Initial Contract Date'];
  }
  if (!empty($item["RDA involvement and/or detail other Lead Agency supporting project"])) {
    $node_data['field_rda_involvement'] = $item['RDA involvement and/or detail other Lead Agency supporting project'];
  }

  if (!empty($item["Project Address"])) {
    $address = $item["Project Address"];
    $node_data['field_address_string'] = $address;
    $max_words = 15;
    if (str_word_count($address,0) >= $max_words) {
      $words = str_word_count($address, 2);
      $pos   = array_keys($words);
      $address  = substr($address, 0, $pos[$max_words]);
    }
    $provider_id = "mapbox";
    $provider = \Drupal::entityTypeManager()->getStorage('geocoder_provider')->load($provider_id);
    $result = $provider->getPlugin()->geocode($address);
    if ($result->count() == 0) {
      \Drupal::logger('rdasa_project_import')->error("Could not resolve the address: " . $address);
    } else {
      $first_result = $result->first();
      $admin_levels = $first_result->getAdminLevels();
      $state = $admin_levels->get(2)->getCode();
      $country = $first_result->getCountry();
      $country_code = $country->getCode();
      $new_address = [
        "country_code" => $country_code,
        "administrative_area" => $state,
        "locality" => $first_result->getLocality(),
        "postal_code" => $first_result->getPostalCode(),
        "address_line1" => $first_result->getStreetNumber() . " " . $first_result->getStreetName()
      ];

      $node_data['field_address'] = $new_address;
      $coordinates = $first_result->getCoordinates();
      $latitude = number_format($coordinates->getLatitude(), 10);
      $longitude = number_format($coordinates->getLongitude(), 10);
      $node_data["field_project_location"] = [
        "lat" => $latitude,
        "lng" => $longitude
      ];
    }
  }
  if (!empty($item["Project Description"])) {
    $node_data['field_project_description'] = $item['Project Description'];
  }
  if (!empty($item["Project Location"])) {
    $node_data['field_project_locality'] = $item['Project Location'];
  }
  if (!empty($item["Council Area"])) {
    $termID = CouncilAreaToTermID($item["Council Area"]);
    $node_data['field_local_government_area'] = $termID;
  }
  if (!empty($item["Industry Sector  (ANZSIC code - subdivision level)"])) {
    $industry_sector = Industry_string_to_code($item["Industry Sector  (ANZSIC code - subdivision level)"]);
    $node_data['field_industry_sector'] = $industry_sector;
  }
  if (!empty($item["Capital Expenditure"])) {
    $node_data['field_capital_expenditure'] = $item['Capital Expenditure'];
  }

  if (!empty($item["Project Stage"])) {
    $node_data['field_project_stage'] = Stage_string_to_code($item['Project Stage']);
  } else {
    \Drupal::logger('rdasa_project_import')->error('Project Stage field not provided');
  }

  if (!empty($item["Timeframe"])) {
    $node_data['field_timeframe_for_commencement'] = Timeframe_string_to_code($item['Timeframe']);
  } else {
    \Drupal::logger('rdasa_project_import')->error('Timeframe field not provided');
  }

  if (!empty($item["FTE construction"])) {
    $node_data['field_fte_construction'] = $item['FTE construction'];
  }
  // Setting a simple textfield to add a unique ID so we can use it to query against if we want to manipulate this data again.
  // $node_data['field_unique_id']['value'] = $item['id'];
  $node = Node::create($node_data);
  $node->setPublished(TRUE);
  $node->save();
  if (!empty($node_data['field_rda_area'])) {
    $group = \Drupal::entityTypeManager()
      ->getStorage('group')->load($node_data['field_rda_area']);
    $group->addRelationship($node, 'group_node:project');
  }
}

Function CouncilAreaToTermID($CouncilArea): int|string|null {
  $vid = 'council_areas';

  $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties([
      'vid' => $vid,
      'name' => $CouncilArea,
    ]);
  $first=array_key_first($terms);
  if(!empty($first)) {
    return $first;
  } else {
    \Drupal::logger('rdasa_project_import')->error("No Council Area Vocabulary term found for $CouncilArea");
    return null;
  }

}
  function Industry_string_to_code($industry_string): int|string|null {
  if (empty($industry_string)) {
    \Drupal::logger('rdasa_project_import')->error("No Industry String provided -  $industry_string");
  }

    $vid = 'industry_sector';

    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'vid' => $vid,
        'name' =>$industry_string,
      ]);

    $first=array_key_first($terms);
    if(!empty($first)) {
      return $first;
    } else {
      \Drupal::logger('rdasa_project_import')->error("No Industry Vocabulary term found for $industry_string");
      return null;
    }
}

function Stage_string_to_code($stage_string): int|string|null {

  $vid = 'project_stage';

  $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties([
      'vid' => $vid,
      'name' =>$stage_string,
    ]);

  $first=array_key_first($terms);
  if(!empty($first)) {
    return $first;
  } else {
    \Drupal::logger('rdasa_project_import')->error("No Stage Vocabulary term found for $stage_string");
    return null;
  }
}

function Timeframe_string_to_code($timeframe_string): int|string|null {

  $vid = 'timeframe';

  $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties([
      'vid' => $vid,
      'name' =>$timeframe_string,
    ]);
  $first=array_key_first($terms);
  if(!empty($first)) {
    return $first;
  } else {
    \Drupal::logger('rdasa_project_import')->error("No Timeframe Vocabulary term found for $timeframe_string");
    return null;
  }}
