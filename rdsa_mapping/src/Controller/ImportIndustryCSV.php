<?php

namespace Drupal\rdasa_mapping\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormInterface;

class ImportIndustryCSV extends ControllerBase {
  /**
   * Display the markup.
   *
   * @return array
   */
  public function content(Request $request) {

    $form = \Drupal::formBuilder()->getForm('Drupal\rdasa_mapping\Form\ImportIndustryCSVForm');

    return $form;
  }
}
