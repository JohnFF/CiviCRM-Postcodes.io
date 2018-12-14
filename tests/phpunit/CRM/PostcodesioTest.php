<?php

use CRM_Postcodesio_ExtensionUtil as E;
use Civi\Test\EndToEndInterface;

/**
 * Tests the main CRM_Postcodesio class.
 *
 * @group e2e
 * @see cv
 */
class CRM_PostcodesioTest extends \PHPUnit_Framework_TestCase implements EndToEndInterface {

  private $testContactId;

  public static function setUpBeforeClass() {
    \Civi\Test::e2e()->installMe(__DIR__)->apply();
  }

  public function setUp() {
    parent::setUp();

    $testContactDetails = civicrm_api3('Contact', 'create', array(
      'contact_type' => 'Individual',
      'first_name' => 'Test for CRM_Postcodesio',
      'last_name' => 'CiviFirst',
    ));

    $this->testContactId = $testContactDetails['id'];
  }

  public function tearDown() {
    parent::tearDown();
  }


  public function testgetData() {
    // Valid results.
    $validResults = CRM_Postcodesio::getData('SE1 2LP');
    $this->assertEquals(200, $validResults['status']);
    $this->assertEquals(-0.076035, $validResults['result'][longitude]);
    $this->assertEquals(51.50266, $validResults['result'][latitude]);

    // Invalid results.
    $validResults = CRM_Postcodesio::getData('Invalid');
    $this->assertEquals(404, $validResults['status']);


    // Invalid results with Exception.
    try {
      $validResults = CRM_Postcodesio::getData('Invalid', TRUE);
      $this->fail('Should have asserted.');
    }
    catch (Exception $exception) {
      $this->assertEquals(CRM_Postcodesio::ERROR_CODE_NO_RESULTS, $exception->getCode());
    }
  }

  public function testSetDistrictForAddress() {
    $testAddress = $this->getNewTestAddress();
    $postcodesIo = new CRM_Postcodesio();
    $postcodesIo->setDistrictForAddress($testAddress['id']);
    $this->assertEquals('Westminster', $this->getTestAddressValue($testAddress['id'], $postcodesIo->getDistrictCustomFieldApiKey()));
  }

  public function testSetGeocodesForAddress() {
    $testAddress = $this->getNewTestAddress();
    $postcodesIo = new CRM_Postcodesio();
    $postcodesIo->setGeocodesForAddress($testAddress['id'], FALSE);
    $this->assertEquals(-0.138861, $this->getTestAddressValue($testAddress['id'], 'geo_code_1'));
    $this->assertEquals(51.495076, $this->getTestAddressValue($testAddress['id'], 'geo_code_2'));
  }

  public function getNewTestAddress() {
    return civicrm_api3('address', 'create', array(
      'address_line_1' => 'Westminster Cathedral',
      'address_line_2' => '42 Francis St',
      'location_type_id' => 1,
      'city' => 'London',
      'postal_code' => 'SW1P 1QW',
      'contact_id' => $this->testContactId,
    ));
  }

  public function getTestAddressValue($addressId, $returnValue) {
    $returnValues = civicrm_api3('Address', 'getvalue', array(
      'id' => $addressId,
      'return' => $returnValue,
    ));

    return $returnValues;
  }
}
