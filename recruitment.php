<?php

require_once 'recruitment.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function recruitment_civicrm_config(&$config) {
  _recruitment_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function recruitment_civicrm_xmlMenu(&$files) {
  _recruitment_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function recruitment_civicrm_install() {
  _recruitment_civix_civicrm_install();
  return recruitment_import();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function recruitment_civicrm_uninstall() {
  return _recruitment_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function recruitment_civicrm_enable() {
  return _recruitment_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function recruitment_civicrm_disable() {
  return _recruitment_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function recruitment_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _recruitment_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function recruitment_civicrm_managed(&$entities) {
  return _recruitment_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function recruitment_civicrm_caseTypes(&$caseTypes) {
  _recruitment_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function recruitment_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _recruitment_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function recruitment_import()
{
  ini_set('max_execution_time', 0);
  
  // get custom group id and table name
  $params = array(
    'version' => 3,
    'q' => 'civicrm/ajax/rest',
    'sequential' => 1,
    'name' => 'AI_Nummer',
  );
  $custom_groups = civicrm_api('CustomGroup', 'get', $params);
  
  if(1 == $custom_groups['is_error']){ // if there is a error
    CRM_Core_Session::setStatus( ts('An error occurred when retrieving the custom group.'), ts('AI nummer'), 'error');
    
  }else {
    $custom_group_id = $custom_groups['values'][0]['id'];
    $custom_group_tabel_name = $custom_groups['values'][0]['table_name'];
  }
  
  // get custom field column name
  if(0 == $custom_groups['is_error']){ // if there is no error
    $params = array(
      'version' => 3,
      'q' => 'civicrm/ajax/rest',
      'sequential' => 1,
      'custom_group_id' => $custom_group_id,
      'name' => 'AI_Nummer',
    );
    $custom_fields = civicrm_api('CustomField', 'get', $params);
    
    if(1 == $custom_fields['is_error']){ // if there is no error
      CRM_Core_Session::setStatus( ts('An error occurred when retrieving the custom field.'), ts('AI nummer'), 'error');
      
    }else {
      $custom_field_id = $custom_fields['values'][0]['id'];
      $custom_field_column_name = $custom_fields['values'][0]['column_name'];
    }
  }
  
  // get all contacts
  $query = "SELECT civicrm_contact.id, civicrm_contact.external_identifier, " . $custom_group_tabel_name . "." . $custom_field_column_name . " FROM civicrm_contact ";
  $query .= "LEFT JOIN `" . $custom_group_tabel_name . "` ON civicrm_contact.id = " . $custom_group_tabel_name . ".entity_id";
  
  $dao = CRM_Core_DAO::executeQuery($query);
  
  // loop trough contacts
  while($dao->fetch()){
    
    if(isset($dao->external_identifier) and '' != $dao->external_identifier){ // if external_identifier exists and is not empty

      // save ai nummer
      if('' == $dao->{$custom_field_column_name}){ // if ai nummer is not empty
        $params = array(
          'version' => 3,
          'q' => 'civicrm/ajax/rest',
          'sequential' => 1,
          'contact_id' => $dao->id,
          'custom_' . $custom_field_id => $dao->external_identifier,
        );
        $ai_nummer_create = civicrm_api('Contact', 'create', $params);

        if(1 == $ai_nummer_create['is_error']){ // if there is no error
          CRM_Core_Session::setStatus( ts('An error occurred when creating the ai nummer.'), ts('AI nummer'), 'error');
        }
      }
    }
  }
  
  return true;
}