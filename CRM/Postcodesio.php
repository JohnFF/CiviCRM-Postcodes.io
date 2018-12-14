<?php

class CRM_Postcodesio {
  const ERROR_CODE_NO_RESULTS = 1000;

  private $districtCustomFieldApiKey;

  public function __construct() {
    $adminDistrictFields = civicrm_api3('CustomField', 'getsingle', array(
      'sequential' => 1,
      'name' => "admin_district",
    ));

    $this->districtCustomFieldApiKey = 'custom_' . $adminDistrictFields['custom_group_id'];
  }

  /**
   *
   * @param string $postCode
   * @param bool $assertIfNoResults throw a CRM_Exception if there are no results.
   * @return array
   */
  public static function getData($postCode, $assertIfNoResults = FALSE) {
    $requestUrl = 'https://api.postcodes.io/postcodes/' . $postCode;

    $postcodesioResults = CRM_Utils_HttpClient::singleton()->get($requestUrl);
    $decodedPostcodesioResults = json_decode($postcodesioResults[1], TRUE);

    if (200 != $decodedPostcodesioResults['status'] && $assertIfNoResults) {
      throw new CRM_Exception('No results found for input postcode.', self::ERROR_CODE_INVALID_POSTCODE);
    }

    return $decodedPostcodesioResults;
  }

  /**
   * Sets the district custom field off a postcode.
   *
   * @param int $addressId
   */
  public function setDistrictForAddress($addressId) {
    $address = civicrm_api3('address', 'getsingle', array('id' => $addressId));

    $decodedResults = $this->getData($address['postal_code']);

    if ($decodedResults['status'] != 200) {
      return;
    }

    civicrm_api3('Address', 'create', array(
      'id' => $addressId,
      $this->districtCustomFieldApiKey => $decodedResults['result']['admin_district'],
    ));
  }

  /**
   *
   * @param int $addressId
   * @param bool $override
   */
  public function setGeocodesForAddress($addressId, $override) {
    $address = civicrm_api3('address', 'getsingle', array('id' => $addressId));

    $decodedResults = $this->getData($address['postal_code']);

    if ($decodedResults['status'] != 200) {
      return;
    }

    if ($override == FALSE && !empty($address['geo_code_1'])) {
      return;
    }

    if (empty($decodedResults['result']['longitude']) || empty($decodedResults['result']['latitude'])) {
      return;
    }

    civicrm_api3('Address', 'create', array(
      'id' => $addressId,
      'geo_code_1' => $decodedResults['result']['longitude'],
      'geo_code_2' => $decodedResults['result']['latitude'],
    ));
  }

  /**
   *
   * @param int $addressId
   * @param bool $overwriteCurrentCounty
   */
  public function setCounty($addressId, $overwriteCurrentCounty) {
    $address = civicrm_api3('address', 'getsingle', array('id' => $addressId));

    $decodedResults = $this->getData($address['postal_code']);

    if ($decodedResults['status'] != 200) {
      return;
    }

    if ($overwriteCurrentCounty == FALSE && !empty($address['county_id'])) {
      return;
    }

    try {
      $county = civicrm_api3('StateProvince', 'getsingle', array(
        'sequential' => 1,
        'name' => $decodedResults['result']['nuts'],
      ));

      civicrm_api3('Address', 'create', array(
        'id' => $addressId,
        'county_id' => $county['id'],
      ));
    }
    catch (Exception $exception) {
      CRM_Core_Error::debug('Postcodes.io - could not retrieve nuts for ' . $decodedResults['result']['nuts'], NULL, TRUE, FALSE);
    }
  }

  /**
   * Used in the context of a search result action.
   *
   * @param int $contactId
   */
  public function setAddressDistrictsForContact($contactId) {

    $addresses = civicrm_api3('Address', 'get', array('contact_id' => $contactId));

    foreach ($addresses['values'] as $eachAddress) {
      $this->setDistrictForAddress($eachAddress['id']);
    }
  }

  /**
   * Used in the context of a search result action.
   *
   * @param int $contactId
   */
  public function setAddressCountiesForContact($contactId, $override = FALSE) {
    $addresses = civicrm_api3('Address', 'get', array('contact_id' => $contactId));

    foreach ($addresses['values'] as $eachAddress) {
      $this->setCounty($eachAddress['id'], $override);
    }
  }

  /**
   * Used in the context of a search result action.
   *
   * @param int $contactId
   */
  public function setAddressGeocodesForContact($contactId, $override = FALSE) {
    $addresses = civicrm_api3('Address', 'get', array('contact_id' => $contactId));

    foreach ($addresses['values'] as $eachAddress) {
      $this->setGeocodesForAddress($eachAddress['id'], $override);
    }
  }

}
