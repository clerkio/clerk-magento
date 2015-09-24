<?php 
class Clerk_Clerk_Model_Feed extends Mage_Core_Helper_Abstract
{
	public function buildFeeds()
	{

		foreach(Mage::app()->getStores() as $store)
		{
			if(Mage::getStoreConfig('clerk/settings/active', $store->getId()) && Mage::getStoreConfig('clerk/datasync/magentocron', $store->getId()) )
			{
				$feedData = array();
                $feedData['products'] = $this->__getFeedProductData($store->getId());
                $feedData['categories'] = $this->__getFeedCategoryData($store->getId());
                if(Mage::getStoreConfig('clerk/datasync/include_historical_salesdata', $store->getId() == -1)){
                    $feedData['sales'] = $this->__getFeedSalesData($store->getId());
                }
				
				$feedData['created'] = (int)time();
				
				$filename = Mage::helper('clerk')->getFileName($store);
				$path = Mage::getBaseDir('media')."/clerk/feeds/";
				
				$file = new Varien_Io_File();
				$file->checkAndCreateFolder($path);
				$file->open(array('path' => $path));
				$file->write($filename,json_encode($feedData,JSON_HEX_QUOT));
				
				Mage::getModel('clerk/communicator')->startImportOfFeed($store->getId());
			}
		}
		return true;
	}

	private function __getFeedProductData($storeId)
	{
		$products = array();
		$feedHelper = Mage::helper('clerk');

		$appEmulation = Mage::getSingleton('core/app_emulation');
		$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);

			$collection = Mage::getModel('catalog/product')->getCollection()->addStoreFilter($storeId);
			
			$filters = Mage::helper('clerk')->getProductCollectionFilters();
			foreach($filters as $key => $value){
				$collection->addFieldToFilter($key,$value);
			}
			
			$collection->setPageSize(500);
			
			while($collection->getCurPage() <= $collection->getLastPageNumber())
			{
				foreach($collection as $product)
				{
					$_product = Mage::getModel('catalog/product')->load($product->getId());
					if(Mage::helper('clerk')->includeOnlySaleableProducts() && !$product->isSaleable()) {
						// Do not include product in feed because its not saleable
					}
					else {
						$data = $feedHelper->getProductData($_product);
						$products[] = $data;
					}
					$_product->clearInstance();
			    }
	
				if($collection->getCurPage() == $collection->getLastPageNumber()) {
					break;
				}
				$collection->setCurPage($collection->getCurPage()+1);
				$collection->clear();
			}

		$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);	
		
		return $products;
	}
	
	
	
	private function __getFeedCategoryData($storeId)
	{
		$categories = array();
		$feedHelper = Mage::helper('clerk');

		$appEmulation = Mage::getSingleton('core/app_emulation');
		$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
			
			$collection = Mage::getModel('catalog/category')->getCollection();
			
			foreach($collection as $category)
			{
				$_category = Mage::getModel('catalog/category')->load($category->getId());
				$data = $feedHelper->getCategoryData($_category);
				if($data['name']) {
					$categories[] = $data;
				}
				$_category->clearInstance();   
			}
			
		$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);	
		
		return $categories;
	}
	
	
	private function __getFeedSalesData($storeId)
	{
		$sales = array();
		$feedHelper = Mage::helper('clerk');
		
		$collection = Mage::getModel('sales/order')->getCollection()
						->addFieldToFilter('store_id',$storeId);
			
		$filters = Mage::helper('clerk')->getSalesCollectionFilters();
		foreach($filters as $key => $value){
			$collection->addFieldToFilter($key,$value);
		}
		
		foreach($collection as $order)
		{
			$_order = Mage::getModel('sales/order')->load($order->getId());
			$data = $feedHelper->getSalesData($_order,true);
			$sales[] = $data;
			$_order->clearInstance();   
		}
		
		return $sales;	
	}
}
