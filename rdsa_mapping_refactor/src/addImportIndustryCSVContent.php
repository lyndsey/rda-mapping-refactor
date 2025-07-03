<?php
namespace Drupal\rdasa_mapping;

use Drupal\facets\Plugin\facets\hierarchy\Taxonomy;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

class addImportIndustryCSVContent {
  public static function addImportIndustryCSVContentItem($item, &$context): void {
    $context['sandbox']['current_item'] = $item;
    $message = 'Creating ' . $item['title'];
    $results = array();
    create_taxonomy($item);
    $context['message'] = $message;
    $context['results'][] = $item;
  }
  function addImportIndustryCSVContentItemCallback($success, $results, $operations): void {
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
  }
}

// This function actually creates each item as a node as type 'Page'
/**
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function create_taxonomy($item): void {
  $term_data['vid'] = 'industry_sector';
  $term_data['name'] = $item[3];
  // Setting a simple textfield to add a unique ID so we can use it to query against if we want to manipulate this data again.
  $new_term = Term::create($term_data);
  $new_term->enforceIsNew();
  $new_term->setPublished(TRUE);
  $new_term->save();
}
