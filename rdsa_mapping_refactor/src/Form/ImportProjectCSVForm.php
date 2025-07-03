<?php
/**
 * @file
 * Contains \Drupal\rdasa_mapping\Form\ImportForm.
 */
namespace Drupal\rdasa_mapping\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;

class ImportProjectCSVForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'project_csv_import_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = array(
      '#markup' => '<p>Use this form to upload a CSV file of Data.   Note, you have to have a value in the column for "Project Name"</p>',
    );

    $form['import_csv'] = array(
      '#type' => 'managed_file',
      '#title' => t('Upload file here'),
      '#upload_location' => 'public://importcsv/',
      '#default_value' => '',
      "#upload_validators"  => array("file_validate_extensions" => array("csv")),
      '#states' => array(
        'visible' => array(
          ':input[name="File_type"]' => array('value' => t('Upload Your File')),
        ),
      ),
    );

    // Group select box
    $group_view = \Drupal\views\Views::getView('groups');
    $group_view->build('entity_reference_1');
    $group_view->execute();
    $group_view->postexecute();
    $group_items = $group_view->result;
    $group_options = array();
    foreach ($group_items as $group_item) {
      $group = $group_item->_entity;
      $id = $group->id();
      $label = $group->get("label")->getString();
      $group_options[$id] = $label;
    }
    $form['project_item_group'] = array(
      '#title' => t('Project Group'),
      '#type' => 'select',
      '#required' => TRUE,
      '#default_value' => 'RDA-SA',
      '#options' => $group_options
    );
    $pubpri = ["0"=>"Private", "1"=>"Public"];
    $form['project_public'] = array(
      '#title' => t('Public or Private Project'),
      '#type' => 'select',
      '#required' => TRUE,
      '#default_value' => '0',
      '#options' => $pubpri
    );

    $header_line = ["1"=>"1", "2"=>"2"];
    $form['header_line'] = array(
      '#title' => t('Header Line'),
      '#type' => 'select',
      '#description' => "This is the row number in the CSV tht has the Header titles.",
      '#required' => TRUE,
      '#default_value' => '1',
      '#options' => $header_line
    );
    $form['actions']['#type'] = 'actions';


    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Upload CSV'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {


    /* Fetch the array of the file stored temporarily in database */
    $csv_file = $form_state->getValue('import_csv');
    $operations = [];
    /* Load the object of the file by it's fid */
    if (empty($csv_file)) {
      \Drupal::messenger()->addMessage("Issue with file ");
      return;
    }
    $file = File::load( $csv_file[0] );

    /* Set the status flag permanent of the file object */
    $file->setPermanent();

    /* Save the file in database */
    $file->save();

    $form_values = [];
    $form_values["project_public"] = $form_state->getValue("project_public");
    $form_values["project_item_group"] = $form_state->getValue("project_item_group");
    $form_values["header_line"] = $form_state->getValue("header_line");

    // You can use any sort of function to process your data. The goal is to get each 'row' of data into an array
    // If you need to work on how data is extracted, process it here.
    $data = $this->csvtoarray($file->getFileUri(), ',', $form_values);
    foreach($data as $id => $row) {
      $operations[] = ['\Drupal\rdasa_mapping\addImportProjectCSVContent::addImportProjectCSVContentItem', [$row, $form_values]];
    }

    $batch = array(
      'title' => t('Importing CSV Data...'),
      'operations' => $operations,
      'init_message' => t('Import is starting.'),
      'finished' => '\Drupal\rdasa_mapping\addImportProjectCSVContent::addImportProjectCSVContentItemCallback',
    );
    batch_set($batch);
  }

  public function csvtoarray($filename, $delimiter, $form_values): bool|array {

    if(!file_exists($filename) || !is_readable($filename)) {
      \Drupal::messenger()->addMessage("Issue with file ". $filename);
      return FALSE;
    }
    $header = NULL;
    $data = array();
    $header_line = $form_values["header_line"];
    if (($handle = fopen($filename, 'r')) !== FALSE ) {
      while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
      {
        if (count($row) == 1 && is_null($row[0])) {
          continue;
        } elseif($header_line > 1) {
          $header_line--;
          continue;
        } elseif (!$header) {
          // trim white space off the keys.
          $header = array_map('trim',$row);
        } else {
          // ignore any blank lines.
          $key = array_search('Project Name', $header);
          if (empty($row[$key])) {
            continue;
          }
          $row = array_map('utf8_encode', $row);
          $data[] = array_combine($header, $row);
        }
      }
      fclose($handle);
    }

    return $data;
  }

}
