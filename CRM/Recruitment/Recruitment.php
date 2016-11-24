<?php

/**
 * Created by PhpStorm.
 * User: erik
 * Date: 20-9-16
 * Time: 6:51
 */
class CRM_Recruitment_Recruitment {

  protected $_historicActivityTypeId = NULL;
  protected $_historicCampaignId = NULL;
  protected $_historicCampaignTypeId = NULL;
  protected $_sourceContactId = NULL;

  /**
   * CRM_Recruitment_Recruitment constructor.
   */
  function __construct() {
    $this->setActivityTypeIds();
    $this->setCampaignTypeIds();
    $this->setCampaignIds();
    $this->setSourceContactId();
  }

  /**
   * Method to retrieve the source contact id. Should be Amnesty International Vlaanderen but also has some escape routes
   */
  private function setSourceContactId() {
    // find contact with legal name Amnesty International Vlaanderen VZW
    try {
      $this->_sourceContactId = civicrm_api3('Contact', 'getvalue', array(
        'legal_name' => 'Amnesty International Vlaanderen VZW',
        'return' => 'id'
      ));

    // if that fails select the first Amnesty International Vlaanderen in Belgium that can be found
    } catch (CiviCRM_API3_Exception $ex) {
      $amnesties = civicrm_api3('Contact', 'get', array(
        'organization_name' => array('LIKE' => "%Amnesty International Vlaanderen%"),
        'country_id' => 1020,
        'options' => array('limit' => 1)
      ));
      // if that fails try with just Amnesty International in Belgium
      if ($amnesties['count'] == 0) {
        $amnesties = civicrm_api3('Contact', 'get', array(
          'organization_name' => array('LIKE' => "%Amnesty International%"),
          'country_id' => 1020,
          'options' => array('limit' => 1)
        ));
        foreach ($amnesties['values'] as $amnestyId => $amnesty) {
          $this->_sourceContactId = $amnestyId;
        }
      }
      foreach ($amnesties['values'] as $amnestyId => $amnesty) {
        $this->_sourceContactId = $amnestyId;
      }
    }
    // if all failed, use 1
    if (empty($this->_sourceContactId)) {
      $this->_sourceContactId = 1;
    }
  }

  /**
   * Method to get or create campaign type for historic campaigns
   *
   * @throws Exception
   */
  private function setCampaignTypeIds() {
    try {
      $this->_historicCampaignTypeId = civicrm_api3('OptionValue', 'getvalue', array(
        'option_group_id' => 'campaign_type',
        'name' => 'historic_campaign',
        'return' => 'value'
      ));
    } catch (CiviCRM_API3_Exception $ex) {
      try {
        $created = civicrm_api3('OptionValue', 'create', array(
          'option_group_id' => 'campaign_type',
          'label' => 'Historic Campaigns',
          'name' => 'historic_campaign',
          'is_active' => 1,
          'is_reserved' => 1
        ));
        $this->_historicCampaignTypeId = $created['values']['value'];

      } catch (CiviCRM_API3_Exception $ex) {
        throw new Exception('Could not find nor create campaign type with name historic_campaign, 
        contact your system administrator. Error from API OptionValue Create: '. $ex->getMessage());
      }
    }
  }

  /**
   * Method to get or create the campaign to use for historic recruitment without rekru
   *
   * @throws Exception
   */
  private function setCampaignIds() {

    try {
      $this->_historicCampaignId = civicrm_api3('Campaign', 'getvalue', array(
        'name' => 'prehistoric_campaign',
        'campaign_type_id' => $this->_historicCampaignTypeId,
        'return' => 'id'
      ));
    } catch (CiviCRM_API3_Exception $ex) {
      try {
        $created = civicrm_api3('Campaign', 'Create', array(
          'name' => 'prehistoric_campaign',
          'title' => 'Prehistorische Campagne voor historische rekrutering',
          'description' => 'Campagne voor historische rekrutering zonder rekrutering',
          'campaign_type_id' => $this->_historicCampaignTypeId,
          'status_id' => 'completed'
        ));
        $this->_historicCampaignId = $created['id'];
      } catch (CiviCRM_API3_Exception $ex) {
        throw new Exception('Could not find nor create campaign with name prehistoric_recruitment, 
        contact your system administrator. Error from API Campaign Create: '. $ex->getMessage());
      }
    }
  }

  /**
   * Method to get or create activity type for historic recruitment
   * @throws Exception
   */
  private function setActivityTypeIds() {
    try {
      $this->_historicActivityTypeId = civicrm_api3('OptionValue', 'getvalue', array(
        'name' => 'historic_recruitment',
        'option_group_id' => 'activity_type',
        'return' => 'value'));
    } catch (CiviCRM_API3_Exception $ex) {
      try {
        $created = civicrm_api3('OptionValue', 'create', array(
          'option_group_id' => 'activity_type',
          'name' => 'historic_recruitment',
          'label' => 'Historische Rekrutering',
          'is_active' => 1,
          'is_reserved' => 1
        ));
        $this->_historicActivityTypeId = $created['values']['value'];
      } catch (CiviCRM_API3_Exception $ex) {
        throw new Exception('Could not find nor create activity type with name historic_recruitment, 
        contact your system administrator. Error from API OptionValue Create: '. $ex->getMessage());
      }
    }
  }

  /**
   * Method to process a historic recruitment
   *
   * @param object $recruitment
   * @return bool
   */
  public function process($recruitment) {
    $testDate = new DateTime($recruitment->action_date);
    // ignore if contact has earlier activity or group membership
    if ($this->hasEarlierActivity($recruitment->contact_id, $testDate) == FALSE) {
      if ($this->hasEarlierGroupMembership($recruitment->contact_id, $testDate) == FALSE) {
        $this->createHistoricRecruitment($recruitment);
      }
    }
  }

  /**
   * Method to check if the contact has an activity earlier than the test date
   *
   * @param int $contactId
   * @param DateTime $testDate
   * @return bool
   */
  protected function hasEarlierActivity($contactId, $testDate) {
    $query = 'SELECT COUNT(*) AS countAct 
      FROM civicrm_activity_contact ac JOIN civicrm_activity act ON ac.activity_id = act.id
      AND act.is_current_revision = %1 WHERE ac.contact_id = %2 AND act.is_deleted = %3 
      AND act.is_test = %3 AND act.activity_date_time <= %4';
    $params = array(
      1 => array(1, 'Integer'),
      2 => array($contactId, 'Integer'),
      3 => array(0, 'Integer'),
      4 => array($testDate->format('Y-m-d'), 'String'));
    $countAct = CRM_Core_DAO::singleValueQuery($query, $params);
    if ($countAct > 0) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Method to check if the contact has an earlier group membership than the test date
   *
   * @param int $contactId
   * @param DateTime $testDate
   * @return bool
   */
  protected function hasEarlierGroupMembership($contactId, $testDate) {
    $query = 'SELECT COUNT(*) AS countSub FROM civicrm_subscription_history 
      WHERE contact_id = %1 AND status = %2 AND date <= %3';
    $params = array(
      1 => array($contactId, 'Integer'),
      2 => array('Added', 'String'),
      3 => array($testDate->format('Y-m-d'), 'String'));
    $countSub = CRM_Core_DAO::singleValueQuery($query, $params);
    if ($countSub > 0) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Method to create new historic recruitment activity for contact
   *
   * @param object $recruitment
   * @return bool|array
   */
  protected function createHistoricRecruitment($recruitment) {
    // put in prehistoric campaign if rekru is empty
    if (empty($recruitment->rekru_id)) {
      $recruitment->campaign_id = $this->_historicCampaignId;
    }
    if (empty($recruitment->campaign_id)) {
      $campaign = $this->createCampaign($recruitment->rekru_id);
      $campaignId = $campaign['id'];
    } else {
      $campaignId = $recruitment->campaign_id;
    }
    switch ($recruitment->type) {
      case 'D':
        $activitySubject = 'Hist. Rekr. Donatie';
        break;
      case 'L':
        $activitySubject = 'Hist. Rekr. Lidmaatschap';
        break;
      case 'S':
        $activitySubject = 'Hist. Rekr. Sympathisant';
        break;
      default:
        $activitySubject = 'Hist. Rekr. (onbekend type)';
        break;
    }
    $params = array(
      'activity_type_id' => $this->_historicActivityTypeId,
      'campaign_id' => $campaignId,
      'target_contact_id' => $recruitment->contact_id,
      'source_contact_id' => $this->_sourceContactId,
      'activity_date_time' => date('Y-m-d', strtotime($recruitment->action_date)),
      'subject' => $activitySubject
    );
    try {
      $activity = civicrm_api3('Activity', 'create', $params);
      return $activity['values'];
    } catch (CiviCRM_API3_Exception $ex) {
      return FALSE;
    }
  }

  /**
   * Method to create new campaign
   *
   * @param int $rekruNr
   * @return bool|array
   */
  protected function createCampaign($rekruNr) {
    $query = 'SELECT * FROM aivl_hist_campaign WHERE id = %1';
    $dao = CRM_Core_DAO::executeQuery($query, array(1 => array($rekruNr, 'Integer')));
    if ($dao->fetch()) {
      $campaignTitle = $rekruNr.' - '.$dao->name;
      $nameParts = explode(' ', $dao->name);
      $campaignName = $rekruNr.'_'.implode('_', $nameParts);
      try {
        civicrm_api3('Campaign', 'Create', array());
      } catch (CiviCRM_API3_Exception $ex) {}
    }
  }
}