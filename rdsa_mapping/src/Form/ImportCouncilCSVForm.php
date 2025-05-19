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

class ImportCouncilCSVForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'council_csv_import_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = array(
      '#markup' => '<p>Use this form to upload an Industry CSV file of Data</p>',
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

    $header_line = ["0" => "none", "1"=>"1", "2"=>"2"];
    $form['header_line'] = array(
      '#title' => t('Header Line'),
      '#type' => 'select',
      '#required' => TRUE,
      '#default_value' => '0',
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
    $form_values["header_line"] = $form_state->getValue("header_line");

    // You can use any sort of function to process your data. The goal is to get each 'row' of data into an array
    // If you need to work on how data is extracted, process it here.
    $data = $this->csvtoarray($file->getFileUri(), ',', $form_values);
    foreach($data as $row) {
      $operations[] = ['\Drupal\rdasa_mapping\addImportCouncilCSVContent::addImportCouncilCSVContentItem', [$row]];
    }

    $batch = array(
      'title' => t('Importing CSV Data...'),
      'operations' => $operations,
      'form_values' => $form_values,
      'init_message' => t('Import is starting.'),
      'finished' => '\Drupal\rdasa_mapping\addImportCouncilCSVContent::addImportCouncilCSVContentItemCallback',
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
        if ($header_line == 0) {
          $data[] = $row;
        } elseif ($header_line > 1) {
          $header_line--;
          continue;
        } elseif (!$header) {
          $header = $row;
        } else{
          $data[] = array_combine($header, $row);
        }
      }
      fclose($handle);
    }

    return $data;
  }

}
