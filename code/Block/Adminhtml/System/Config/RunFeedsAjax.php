<?php
class Clerk_Clerk_Block_Adminhtml_System_Config_RunFeedsAjax extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
    	$html = '';
    	
    	$stores = $this->getFeedsToBuild();
    	
    	$html .= '<script type="text/javascript">';
		
		$html .= "
			function buildAllAjaxFeed() {
				_clerk_stores = ".json_encode($stores).";
				
				for (_clerk_store_id in _clerk_stores) {
					for(_clerk_type in _clerk_stores[_clerk_store_id]) {
						_clerk_pages = _clerk_stores[_clerk_store_id][_clerk_type];
						if(_clerk_pages) {
							break;
						}
					}
					if(!_clerk_pages) {
						_clerk_type = 'done';
					}
					break;
				}
				buildAjaxFeed(_clerk_stores,_clerk_store_id,_clerk_type,_clerk_pages,1)
			}
			
			function buildAjaxFeed(_clerk_stores,_clerk_store_id,_clerk_type,_clerk_pages,page)
			{
				if(_clerk_type != 'done') {
					var html = '<span class=\"storeid\">Store Id: '+_clerk_store_id+'</span><br/><span class=\"type\">'+(_clerk_type.charAt(0).toUpperCase()+_clerk_type.slice(1))+'</span>&nbsp;<span class=\"page\">'+Math.round(((page-1)/_clerk_pages)*100)+'%</span>';					
				} else {
					var html = '<span class=\"storeid\">Store Id: '+_clerk_store_id+'</span><br/><span class=\"type\">'+(_clerk_type.charAt(0).toUpperCase()+_clerk_type.slice(1))+'</span>';
				}

				if($$('#loading_mask_loader #status').length) {
					
					$$('#loading_mask_loader #status')[0].innerHTML = html;

				} else {
					var status = new Element('div', {
						'id': 'status'
					});
					status.innerHTML = html;
					$('loading_mask_loader').appendChild(status);
				}
				
				var ajaxUrl = '".$this->getUrl('clerk/adminhtml_feeds/ajax')."';
				new Ajax.Request(ajaxUrl, {
					method: 'post',
					parameters: {store_id:_clerk_store_id,type:_clerk_type,page:page},
					onComplete: function(transport) {
						$($$('#loading_mask_loader #status')[0]).remove();
		    			if(page < _clerk_pages) {
			    			page++;
			    			buildAjaxFeed(_clerk_stores,_clerk_store_id,_clerk_type,_clerk_pages,page);
		    			} 
		    			else 
		    			{
			    			delete(_clerk_stores[_clerk_store_id][_clerk_type]);
			    			if(_clerk_type == 'done') {
				    			delete(_clerk_stores[_clerk_store_id]);
				    		}
							_clerk_type = false;
							_clerk_pages = false;
							for (_clerk_store_id in _clerk_stores) {
								for(_clerk_type in _clerk_stores[_clerk_store_id]) {
									_clerk_pages = _clerk_stores[_clerk_store_id][_clerk_type];
									if(_clerk_pages) {
										break;
									}
								}
								if(!_clerk_pages) {
									_clerk_type = 'done';
								}
								break;
							}
							if(_clerk_type) {
								buildAjaxFeed(_clerk_stores,_clerk_store_id,_clerk_type,_clerk_pages,1);								
							} else {
								window.location = window.location;
							}
		    			}
					}
				});
			}
			
		</script>";
    	
    	$javascript = "buildAllAjaxFeed();";
    	
    	$html .= $this->getLayout()->createBlock('adminhtml/widget_button')
    		->setLabel(Mage::helper('clerk')->__('Build Feeds'))
            ->setOnClick('javascript: '.$javascript)
            ->setType('button')
            ->setClass('scalable')
            ->toHtml();
				
		return $html;
			
	}
	
	private function getFeedsToBuild()
	{
		$storeId = false;
		$websiteId = false;
		if($storecode = Mage::app()->getRequest()->getParam('store')) 
		{
		    $storeCollection = Mage::getModel('core/store')->getCollection()->addFieldToFilter('code', $storecode);        
		    $storeId = $storeCollection->getFirstItem()->getStoreId();
		} 
		elseif($websitecode = Mage::app()->getRequest()->getParam('website')) 
		{
			$websiteCollection = Mage::getModel('core/website')->getCollection()->addFieldToFilter('code', $websitecode);
			$websiteId = $websiteCollection->getFirstItem()->getWebsiteId();
		}
		
		$stores = array();

		foreach(Mage::app()->getStores() as $store)
		{
			if($storeId !== false) {
				if($store->getId() != $storeId) {
					continue;
				}
			} elseif($websiteId !== false) {
				if($store->getWebsiteId() != $websiteId) {
					continue;
				}
			}
			if(Mage::getStoreConfig('clerk/settings/active',$store->getId()))
			{	
				$buildFeed = false;
				$buildFeeds = array();
				if(Mage::getStoreConfig('clerk/feeds/create_product_data',$store->getId())) 
				{
					$buildFeed = true;
					$appEmulation = Mage::getSingleton('core/app_emulation');
					$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($store->getId());
			
						$collection = Mage::getModel('catalog/product')->getCollection();
						
						$filters = Mage::helper('clerk')->getProductCollectionFilters();
						foreach($filters as $key => $value){
							$collection->addFieldToFilter($key,$value);
						}
						
						$collection->setPageSize(Mage::getModel('clerk/feedAjax')->getPageSize($collection));
						
						$buildFeeds['products'] = $collection->getLastPageNumber();
						
					$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
				} else {
					$buildFeeds['products'] = 0;
				}
				
				if(Mage::getStoreConfig('clerk/feeds/create_category_data',$store->getId())) 
				{
					$buildFeed = true;
					$appEmulation = Mage::getSingleton('core/app_emulation');
					$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($store->getId());
			
						$collection = Mage::getModel('catalog/category')->getCollection();
						$collection->setPageSize(Mage::getModel('clerk/feedAjax')->getPageSize($collection));
						
						$buildFeeds['categories'] = $collection->getLastPageNumber();
						
					$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
	
				} else {
					$buildFeeds['categories'] = 0;
				}
				
				if(Mage::getStoreConfig('clerk/feeds/create_sales_data',$store->getId())) 
				{
					$buildFeed = true;
					$appEmulation = Mage::getSingleton('core/app_emulation');
					$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($store->getId());
		
						$collection = Mage::getModel('sales/order')->getCollection()
										->addFieldToFilter('store_id',$store->getId());
				
						$filters = Mage::helper('clerk')->getSalesCollectionFilters();
						foreach($filters as $key => $value){
							$collection->addFieldToFilter($key,$value);
						}
			
						$collection->setPageSize(Mage::getModel('clerk/feedAjax')->getPageSize($collection));
					
						$buildFeeds['sales'] = $collection->getLastPageNumber();
						
					$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
	
				} else {
					$buildFeeds['sales'] = 0;
				}
				
				if($buildFeed && !empty($buildFeeds))
				{
					$stores[$store->getId()] = $buildFeeds;
				}
			}
		}

		return $stores;
	}
	
	
}
