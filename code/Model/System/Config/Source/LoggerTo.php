<?php


class Clerk_Clerk_Model_System_Config_Source_LoggerTo
{

    /**
     * Get powerstep types
     *
     * @return array
     */
    public function toOptionArray()
    {
        $logger_to = array(
            array(
                'value' => 'local',
                'label' => Mage::helper('clerk')->__('Local'),
            ),
            array(
                'value' => 'collect',
                'label' => Mage::helper('clerk')->__('Collect'),
            ),
        );

        return $logger_to;
    }

}