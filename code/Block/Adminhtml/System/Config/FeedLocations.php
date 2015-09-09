<?php
class Clerk_Clerk_Block_Adminhtml_System_Config_FeedLocations extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{
	public function render(Varien_Data_Form_Element_Abstract $element)
	{
		$html = '<div style="padding-bottom: 10px; border-bottom: 1px solid #ccc; margin: 0px 5px 10px;">';
		$html .= '<strong>Insert under Data sync at <a href="http://my.clerk.io" target="blank">my.clerk.io</a></strong><br/>';
		$params = Mage::app()->getRequest()->getParams();
		if(!isset($params['website']) && !isset($params['store']))
		{
			$stores = Mage::app()->getStores();
			foreach($stores as $store)
			{
				$filename = Mage::helper('clerk')->getFileName($store);
				$url = $store->getBaseUrl()."media/clerk/feeds/".$filename;
				if(file_exists(Mage::getBaseDir('base')."/media/clerk/feeds/".$filename)) {
					$html .= '<a href="'.$url.'" target="_black">'.$url.'</a>&nbsp;(Last Modified: '.Mage::getModel('core/date')->date("d-m-Y H:i:s", filemtime(Mage::getBaseDir('base')."/media/clerk/feeds/".$filename)).')<br/>';
				} else {
					$html .= "Feed not build yet: $url<br/>";
				}	
			}
		} elseif(isset($params['website']) && !isset($params['store']))
		{
			$website = Mage::getModel('core/website')->load($params['website'],'code');
			$stores = $website->getStores();
			foreach($stores as $store)
			{
				$filename = Mage::helper('clerk')->getFileName($store);
				$url = $store->getBaseUrl()."media/clerk/feeds/".$filename;
				if(file_exists(Mage::getBaseDir('base')."/media/clerk/feeds/".$filename)) {
					$html .= '<a href="'.$url.'" target="_black">'.$url.'</a>&nbsp;(Last Modified: '.Mage::getModel('core/date')->date("d-m-Y H:i:s", filemtime(Mage::getBaseDir('base')."/media/clerk/feeds/".$filename)).')<br/>';
				} else {
					$html .= "Feed not build yet: $url<br/>";
				}
			}
		}
		elseif(isset($params['website']) && isset($params['store']))
		{
			$store = Mage::getModel('core/store')->load($params['store'],'code');

			$filename = Mage::helper('clerk')->getFileName($store);
			$url = $store->getBaseUrl()."media/clerk/feeds/".$filename;
			if(file_exists(Mage::getBaseDir('base')."/media/clerk/feeds/".$filename)) {
				$html .= '<a href="'.$url.'" target="_black">'.$url.'</a>&nbsp;(Last Modified: '.Mage::getModel('core/date')->date("d-m-Y H:i:s", filemtime(Mage::getBaseDir('base')."/media/clerk/feeds/".$filename)).')<br/>';				
			} else {
				$html .= "Feed not build yet: $url<br/>";
			}
		}
		
		$html .= '</div>';
		

		
		return $html;
			
	}
}