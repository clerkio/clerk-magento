<?php
class Clerk_Clerk_Model_Communicator extends Mage_Core_Helper_Abstract
{
	protected $_addEndpoint = 'https://api.clerk.io/v2/product/add';
	protected $_removeEndpoint = 'https://api.clerk.io/v2/product/remove';
	protected $_feedImportStartEndpoint = 'https://api.clerk.io/v2/client/account/importer/start';

	public function updateProductId($productId)
	{
		foreach(Mage::app()->getStores() as $store)
		{
			if(Mage::getStoreConfig('clerk/settings/active',$store->getId()) && Mage::getStoreConfig('clerk/productapi/active',$store->getId()))
			{
				$appEmulation = Mage::getSingleton('core/app_emulation');
				$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($store->getId());

				if($this->_syncProductId($productId)) {
					$product = Mage::getModel('catalog/product')->load($productId);
					$data = Mage::helper('clerk')->getProductData($product);
					$data['key'] = Mage::helper('clerk')->getApiKey($store->getId());
					$data['private_key'] = Mage::helper('clerk')->getPrivateApiKey($store->getId());
					if(Mage::helper('clerk')->includeOnlySaleableProducts() && !$product->isSaleable()) {
						$this->sendData($data,$this->_removeEndpoint);
					} else {
						$this->sendData($data,$this->_addEndpoint);
					}
				}
				else
				{
					$data['id'] = $productId;
					$data['key'] = Mage::helper('clerk')->getApiKey($store->getId());
					$data['private_key'] = Mage::helper('clerk')->getPrivateApiKey($store->getId());
					$this->sendData($data,$this->_removeEndpoint);
				}

				$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
			}
		}

	}

	public function startImportOfFeed($storeId)
	{
		$data = array();
		$data['key'] = Mage::helper('clerk')->getApiKey($storeId);
		$data['private_key'] = Mage::helper('clerk')->getPrivateApiKey($storeId);
		$this->sendData($data,$this->_feedImportStartEndpoint);
	}


	public function sendData($data,$endpoint)
	{
		try
		{
			$ch = curl_init($endpoint);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data,JSON_HEX_QUOT));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT_MS, 200);
			curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
			curl_exec($ch);
		}
		catch(Exception $e)
		{
			Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			Mage::log($e->getMessage(),null,"clerk.log",true);
		}

	}

	private function _syncProductId($productId)
	{
		$collection = Mage::getModel('catalog/product')->getCollection()
						->addWebsiteFilter(Mage::app()->getStore()->getWebsiteId())
						->addFieldToFilter('entity_id',$productId);

		$filters = Mage::helper('clerk')->getProductCollectionFilters();
		foreach($filters as $key => $value){
			$collection->addFieldToFilter($key,$value);
		}

		if($collection->getSize()) {
			return true;
		}

		return false;
	}

}