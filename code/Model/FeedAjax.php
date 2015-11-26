<?php
class Clerk_Clerk_Model_FeedAjax extends Mage_Core_Helper_Abstract
{
	public $pageSize = 500;

	public function getPageSize($collection = false)
	{
		if($collection) {
			return ($collection->getSize()%$this->pageSize) ? $this->pageSize : 501;
		}
		return $this->pageSize;
	}

	public function buildFeeds($storeId,$type,$page)
	{
		$store = Mage::app()->getStore($storeId);
		if(Mage::getStoreConfig('clerk/settings/active',$store->getId()))
		{
			$feedData = array();
			if($type == 'products') {
				$feedData[$type] = $this->__getFeedProductData($store->getId(),$page);
			}
			if($type == 'categories') {
				$feedData[$type] = $this->__getFeedCategoryData($store->getId(),$page);
			}
			if($type == 'sales') {
				$feedData[$type] = $this->__getFeedSalesData($store->getId(),$page);
			}
			if($type != 'done') {

				$filename_tmp = Mage::helper('clerk')->getFileName($store,$tmp = true);
				$path = Mage::getBaseDir('media')."/clerk/feeds/";

				$file = new Varien_Io_File();
				$file->checkAndCreateFolder($path);
				$file->open(array('path' => $path));
				if(file_exists($path.$filename_tmp)) {
					$content = $file->read($filename_tmp);
					$json = json_decode($content,true);
					if(isset($json[$type])) {
						if($type == 'products') {
							$add = true;
							foreach($json[$type] as $item_added) {
								if($item_added['id'] == $feedData[$type][0]['id'])
								{
									$add = false;
									break;
								}
							}
							if($add) {
								$json[$type] = array_merge($json[$type],$feedData[$type]);
							}
						} else {
							$json[$type] = array_merge($json[$type],$feedData[$type]);
						}
					} else {
						$json[$type] = $feedData[$type];
					}
					$json['created'] = (int)time();
					$file->write($filename_tmp,json_encode($json,JSON_HEX_QUOT));
				} else {
					$feedData['created'] = (int)time();
					$file->write($filename_tmp,json_encode($feedData,JSON_HEX_QUOT));
				}
			} else {
				$filename_tmp = Mage::helper('clerk')->getFileName($store,$tmp = true);
				$filename = Mage::helper('clerk')->getFileName($store,$tmp = false);
				$path = Mage::getBaseDir('media')."/clerk/feeds/";

				$file = new Varien_Io_File();
				$file->checkAndCreateFolder($path);
				$file->open(array('path' => $path));
				$file->mv($filename_tmp,$filename);
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('clerk')->__("Done building feed. Data stored in %s",$filename));
				Mage::getModel('clerk/communicator')->startImportOfFeed($storeId);
			}
		}

		return true;
	}

	private function __getFeedProductData($storeId,$page)
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

			$collection->setPageSize($this->pageSize);
			$collection->setCurPage($page);

			foreach($collection as $product)
			{
				$_product = Mage::getModel('catalog/product')->load($product->getId());
				if(Mage::helper('clerk')->includeOnlySaleableProducts() && !$product->isSaleable()) {
					// Do not include product in feed because it is not saleable
				}
				else {
					$data = $feedHelper->getProductData($_product);
					$products[] = $data;
				}
				$_product->clearInstance();
		    }

			$collection->clear();

		$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
		unset($initialEnvironmentInfo);
		unset($appEmulation);

		return $products;
	}

	private function __getFeedCategoryData($storeId,$page)
	{
		$categories = array();
		$feedHelper = Mage::helper('clerk');

		$appEmulation = Mage::getSingleton('core/app_emulation');
		$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);

			$collection = Mage::getModel('catalog/category')->getCollection();

			$pageSize = $this->getPageSize($collection);
			$collection->setPageSize($pageSize);
			$collection->setCurPage($page);

			foreach($collection as $category)
			{
				$_category = Mage::getModel('catalog/category')->load($category->getId());
				$data = $feedHelper->getCategoryData($_category);
				if($data['name']) {
					$categories[] = $data;
				}
				$_category->clearInstance();
			}

			$collection->clear();

		$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

		return $categories;
	}


	private function __getFeedSalesData($storeId,$page)
	{
		$sales = array();
		$feedHelper = Mage::helper('clerk');

		$collection = Mage::getModel('sales/order')->getCollection()
						->addFieldToFilter('store_id',$storeId);

		$filters = Mage::helper('clerk')->getSalesCollectionFilters();
		foreach($filters as $key => $value){
			$collection->addFieldToFilter($key,$value);
		}

		$pageSize = $this->getPageSize($collection);
		$collection->setPageSize($pageSize);
		$collection->setCurPage($page);

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
