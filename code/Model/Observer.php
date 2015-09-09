<?php
class Clerk_Clerk_Model_Observer
{
	public function updateProduct($observer)
	{
		if(Mage::getStoreConfig('clerk/settings/active')){
			$product = $observer->getEvent()->getProduct();
			Mage::getModel('clerk/communicator')->updateProductId($product->getId());
		}
	}

	public function updateProductIds($observer)
	{
		if(Mage::getStoreConfig('clerk/settings/active')){
			$productIds = $observer->getEvent()->getProductIds();
			foreach($productIds as $productId)
			{
				Mage::getModel('clerk/communicator')->updateProductId($productId);
			}
		}
	}

	public function updateStockProductIds($observer)
	{
		if(Mage::getStoreConfig('clerk/settings/active')){
			$stock_item = $observer->getEvent()->getItem();
			Mage::getModel('clerk/communicator')->updateProductId($stock_item->getProductId());
		}
	}

	public function setProductAdded($observer)
	{
		if(Mage::getStoreConfig('clerk/settings/active') && Mage::getStoreConfig('clerk/powerpopup/active')) {
			$request = $observer->getEvent()->getRequest();
			if (($request->getActionName() == 'add') && !$request->getParam('in_cart')) {
				$product = $observer->getEvent()->getProduct();
				Mage::getModel('core/cookie')->set('clerk_power_popup',$product->getId(),300);
				if(Mage::getStoreConfig('clerk/powerpopup/type') == 'landingpage') {
					$request = $observer->getEvent()->getRequest();
					$request->setParam('return_url',Mage::getBaseUrl().'checkout/cart/clerk');
				}
			}
		}
	}
}
