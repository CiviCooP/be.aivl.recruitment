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
    'name' => 'Recruitment',
  );
  $custom_groups = civicrm_api('CustomGroup', 'get', $params);
  
  if(1 == $custom_groups['is_error']){ // if there is a error
    CRM_Core_Session::setStatus( ts('An error occurred when retrieving the custom group.'), ts('Recruitment'), 'error');
    return false;
    
  }else {
    $custom_group_id = $custom_groups['values'][0]['id'];
    $custom_group_tabel_name = $custom_groups['values'][0]['table_name'];
  }
  
  unset($params);
  unset($custom_groups);
  
  // get custom field column name
  $params = array(
    'version' => 3,
    'q' => 'civicrm/ajax/rest',
    'sequential' => 1,
    'custom_group_id' => $custom_group_id,
    'name' => 'Recruitment',
  );
  $custom_fields = civicrm_api('CustomField', 'get', $params);

  if(1 == $custom_fields['is_error']){ // if there is a error
    CRM_Core_Session::setStatus( ts('An error occurred when retrieving the custom field.'), ts('Recruitment'), 'error');
    return false;
    
  }else {
    $custom_field_id = $custom_fields['values'][0]['id'];
    $custom_field_column_name = $custom_fields['values'][0]['column_name'];
  }
  
  unset($params);
  unset($custom_fields);
    
  // get option group
  $params = array(
    'version' => 3,
    'q' => 'civicrm/ajax/rest',
    'sequential' => 1,
    'name' => 'recruitment_20140307095917',
  );
  $option_groups = civicrm_api('OptionGroup', 'get', $params);
  
  if(1 == $option_groups['is_error']){ // if there is a error
    CRM_Core_Session::setStatus( ts('An error occurred when retrieving the option group.'), ts('Recruitment'), 'error');
    return false;
    
  }else {
    $option_group_id = $option_groups['values'][0]['id'];
  }
  
  unset($params);
  unset($option_groups);
    
  // get all contacts
  $query = "SELECT civicrm_contact.id, civicrm_contact.source, " . $custom_group_tabel_name . "." . $custom_field_column_name . " FROM civicrm_contact ";
  $query .= "LEFT JOIN `" . $custom_group_tabel_name . "` ON civicrm_contact.id = " . $custom_group_tabel_name . ".entity_id ";
  $query .= "ORDER BY civicrm_contact.id ASC ";

  $dao_contact = CRM_Core_DAO::executeQuery($query);

  // loop trough contacts
  while($dao_contact->fetch()){

    if(isset($dao_contact->source) and '' != $dao_contact->source){ // if external_identifier exists and is not empty

      // get source code like 0500
      if(false === strpos($dao_contact->source, '-')){
        $source_code = trim($dao_contact->source);
      }else {
        $source_code = trim(substr($dao_contact->source, 0, strpos($dao_contact->source, '-')));
      }

      // get option value
      $params = array(
        'version' => 3,
        'q' => 'civicrm/ajax/rest',
        'sequential' => 1,
        'option_group_id' => $option_group_id,
        'value' => $source_code,
      );
      $options_values = civicrm_api('OptionValue', 'get', $params);

      if(1 == $options_values['is_error']){ // if there is a error
        CRM_Core_Session::setStatus( ts('An error occurred when retrieving the option value.'), ts('Recruitment'), 'error');
        return false;
      }
      
      unset($params);
      
      // if option group does not exists
      if(empty($options_values['values'])){
        // create option group
        $params = array(
          'version' => 3,
          'q' => 'civicrm/ajax/rest',
          'sequential' => 1,
          'option_group_id' => $option_group_id,
          'value' => $source_code,
          'label' => $dao_contact->source,
        );
        $options_values_create = civicrm_api('OptionValue', 'create', $params);
        
        if(1 == $options_values_create['is_error']){ // if there is a error
          CRM_Core_Session::setStatus( ts('An error occurred when createting a option value.'), ts('Recruitment'), 'error');
          return false;
        }
        
        unset($params);
        unset($options_values_creates);
      }
      
      unset($options_values);
      
      // save recruitment
      if('' == $dao_contact->{$custom_field_column_name}){ // if recruitment is empty
        $query = "INSERT INTO `" . $custom_group_tabel_name . "` ";
        $query .= "(id, entity_id, " . $custom_field_column_name . ") ";
        $query .= "VALUES ('', '" . $dao_contact->id . "', '" . $source_code . "') ";
        $query .= "ON DUPLICATE KEY UPDATE " . $custom_field_column_name . "='" . $source_code . "' ";

        $dao_recruitment = CRM_Core_DAO::executeQuery($query);

        unset($dao_recruitment);
      }
    }
  }
  
  unset($dao_contact);
  
  return true;
}

function recruitment_civicrm_summary( $contactID, &$content, &$contentPlacement = CRM_Utils_Hook::SUMMARY_BELOW )
{
  $params = array(
    'version' => 3,
    'q' => 'civicrm/ajax/rest',
    'sequential' => 1,
    'name' => 'Recruitment',
  );
  $result = civicrm_api('CustomGroup', 'get', $params);
  
  $content .= '<script type="text/javascript">' . PHP_EOL;
  $content .= 'cj( document ).ready(function() {' . PHP_EOL;
  $content .= 'var parent_el_contact_id_ = cj("#contact-summary .crm-summary-block .crm-contact-contact_id").parent().parent();' . PHP_EOL;
  $content .= 'cj("#customFields .crm-custom-set-block-' . $result['values'][0]['id'] . ' .crm-summary-block").addClass("crm-summary-row");' . PHP_EOL;
  $content .= 'cj("#customFields .crm-custom-set-block-' . $result['values'][0]['id'] . ' .crm-summary-block").insertAfter(parent_el_contact_id_);' . PHP_EOL;
  $content .= 'cj("#customFields .crm-custom-set-block-' . $result['values'][0]['id'] . '").hide();' . PHP_EOL;
  $content .= '});' . PHP_EOL;
  $content .= '</script>' . PHP_EOL;
}