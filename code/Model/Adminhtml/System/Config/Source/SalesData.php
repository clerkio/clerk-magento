<?php
class Clerk_Clerk_Model_Adminhtml_System_Config_Source_SalesData
{
	public function toOptionArray() 
	{
		$power_types = array();
		
		$power_types[] = array(
						'value' => 0,
						'label' => Mage::helper('clerk')->__('No')
					);

		$power_types[] = array(
						'value' => 30,
						'label' => Mage::helper('clerk')->__('1 month')
					);

		$power_types[] = array(
						'value' => 90,
						'label' => Mage::helper('clerk')->__('3 month')
					);

		$power_types[] = array(
						'value' => 365,
						'label' => Mage::helper('clerk')->__('1 year')
					);

		$power_types[] = array(
						'value' => -1,
						'label' => Mage::helper('clerk')->__('all')
					);

		return $power_types;
	}
}
