<?php

class Clerk_Clerk_Model_System_Config_Backend_AttributeLabels extends Mage_Core_Model_Config_Data
{
    /**
     * JSON encode value
     *
     * @return Mage_Core_Model_Abstract|void
     */
    protected function _beforeSave()
    {
        $this->setValue(json_encode(array_filter((array) $this->getValue())));
    }

}