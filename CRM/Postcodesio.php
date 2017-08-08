<?php

class CRM_Postcodesio {
  private $districtCustomFieldApiKey;

  public function __construct() {
    $adminDistrictFields = civicrm_api3('CustomField', 'getsingle', array(
      'sequential' => 1,
      'name' => "admin_district",
    ));

    $this->districtCustomFieldApiKey = 'custom_' . $adminDistrictFields['custom_group_id'];
  }

  /**
   * Sets the district custom field off a postcode.
   *
   * @param int $addressId
   */
  public function setDistrictForAddress($addressId) {
    $address = civicrm_api3('address', 'getsingle', array('id' => $addressId));

    $requestUrl = 'https://api.postcodes.io/postcodes/' . $address['postal_code'];

    $postcodesioResults = CRM_Utils_HttpClient::singleton()->get($requestUrl);
    $decodedResults = json_decode($postcodesioResults[1], TRUE);

    if ($decodedResults['status'] != 200) {
      return;
    }

    civicrm_api3('Address', 'create', array(
      'id' => $addressId,
      $this->districtCustomFieldApiKey => $decodedResults['result']['admin_district']
    ));
  }

  /**
   * Used in the context of a search result action.
   *
   * @param int $contactId
   */
  public function setAddressDistrictsForContact($contactId) {

    $addresses = civicrm_api3('Address', 'get', array('contact_id' => $contactId));

    foreach($addresses['values'] as $eachAddress) {
      $this->setDistrictForAddress($eachAddress['id']);
    }
  }
  
}
