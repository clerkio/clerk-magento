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

    /**
     * The function is run by the observer when a new product is added to the cart
     */
	public function itemAddedToCard($observer)

	{
        // Early return if module or powerstep is disabled
        $clerk_is_active = Mage::getStoreConfig('clerk/settings/active');
        $powerstep_is_active = Mage::getStoreConfig('clerk/features/powerstep_active');
        if (!($clerk_is_active && $powerstep_is_active)){ return; }
        
        // Not sure about these options, was in old codebase
        $request = $observer->getEvent()->getRequest();
        //$action_is_add = $request->getActionName() == 'add';
        //$param_is_not_in_cart = !$request->getParam('in_cart');
        //if (!($action_is_add && $param_is_not_in_cart)) { return; }

        // set cookie clerk_powerstep
        $product = $observer->getEvent()->getProduct();
        $type = Mage::getStoreConfig('clerk/features/powerstep_type');

        // Signal that a new item was added to card
        Mage::getSingleton('core/session')->setShowClerkPowerstep(true);

        // set return url for current action
        switch ($type) {
            // If type is custom_cart, we will take the user to the custom cart
            case 'landingpage':
                $request->setParam('return_url', Mage::getBaseUrl().'checkout/cart/clerk');
                break;
            // If pop up we will stay on the same page 
            case 'popup':
                $referer = $request->getHeader('referer');
                $request->setParam('return_url', $referer);
                break;
        }
	}
}
