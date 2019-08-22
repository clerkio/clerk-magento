<?php


class Clerk_Clerk_Model_System_Config_Source_LoggerLevel
{

    /**
     * Get powerstep types
     *
     * @return array
     */
    public function toOptionArray()
    {
        $logger_level = array(
            array(
                'value' => 'warn',
                'label' => Mage::helper('clerk')->__('Warn'),
            ),
            array(
                'value' => 'error',
                'label' => Mage::helper('clerk')->__('Error'),
            ),
            array(
                'value' => 'all',
                'label' => Mage::helper('clerk')->__('All'),
            ),
        );

        return $logger_level;
    }

}