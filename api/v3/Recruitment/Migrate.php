<?php

/**
 * Recruitment.Migrate API
 * This API migrates the historic AIVL recruitment data:
 * - reads oldest date from aivl_hist_action for contact_id (in batches of 5000)
 * - checks if there is an older activity in CiviCRM for the contact OR if thecere is an earlier
 *   group membership in CiviCRM for the contact
 * - if no older CiviCRM activity/group membership is found, it adds an activity of the type
 *   'Historische Rekrutering' with type in subject to the contact with the date and campaign of the historic
 *   activity. If necessary, a new campaign will be created
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date Sep 2016
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_recruitment_Migrate($params) {
  $count = 0;
  $previousContactId = 0;
  $query = 'SELECT * FROM aivl_hist_action WHERE processed = 0 ORDER BY ai_id, action_date LIMIT 1500';
  $dao = CRM_Core_DAO::executeQuery($query, array(1 => array(0, 'Integer')));
  $recruitment = new CRM_Recruitment_Recruitment();
  while ($dao->fetch()) {
    if ($dao->contact_id != $previousContactId) {
      $previousContactId = $dao->contact_id;
      $recruitment->process($dao);
      $update = 'UPDATE aivl_hist_action SET processed = %1 WHERE contact_id = %2';
      CRM_Core_DAO::executeQuery($update, array(
        1 => array(1, 'Integer'),
        2 => array($dao->contact_id, 'Integer')
      ));
      $count++;
    }
  }
  if ($count > 0) {
    $returnValues = array('Historic recruitment migration processed for ' . $count . ' contacts');
  } else {
    $returnValues = array('Historic recruitment processed for all contacts');
  }
  return civicrm_api3_create_success($returnValues, $params, 'Recruitment', 'Migrate');
}

