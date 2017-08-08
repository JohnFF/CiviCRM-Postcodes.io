<?php

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Postcodesio_Form_Task_LookupDistricts extends CRM_Contact_Form_Task {
  public function buildQuickForm() {

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Lookup districts for these contacts\' addresses.'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function postProcess() {
    $postcodesioProcessor = new CRM_Postcodesio();

    foreach($this->_contactIds as $eachContactId) {
      $postcodesioProcessor->setAddressDistrictsForContact($eachContactId);
    }

    CRM_Core_Session::setStatus(ts('Finished looking up districts for addresses.'));

    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
