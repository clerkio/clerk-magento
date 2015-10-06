<?php
class Clerk_Clerk_Block_Adminhtml_System_Config_FeedLocations extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface

{
    // TODO(brunsgaard): Rewrite function because of code quality
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
                $url = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB)."media/clerk/feeds/".$filename;
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


        // Show nothing if feed is not yes build
        $filename = Mage::helper('clerk')->getFileName($store);
        if (!file_exists(Mage::getBaseDir('base') . "/media/clerk/feeds/" . $filename)) {
            return '';
        }

        if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore())) // store level
        {
            $store_id = Mage::getModel('core/store')->load($code)->getId();
        }
        elseif (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite())) // website level
        {
            $website_id = Mage::getModel('core/website')->load($code)->getId();
            $store_id = Mage::app()->getWebsite($website_id)->getDefaultStore()->getId();
        }
        else // default level
        {
            $store_id = 0;
        }

        if(!Mage::helper('clerk')->getPrivateApiKey($store_id) || !Mage::getStoreConfig('clerk/settings/active', $store_id)) {
            return '';
        }
        

        
        return $html;
            
    }
}
