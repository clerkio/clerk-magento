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
                'value' => 'collect',
                'label' => Mage::helper('clerk')->__('my.clerk.io'),
            ),
            array(
                'value' => 'file',
                'label' => Mage::helper('clerk')->__('File'),
            ),
        );

        return $logger_to;
    }

}