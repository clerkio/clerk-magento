<?php

class Clerk_Clerk_Model_Observer
{
    const XML_PATH_CATEGORY_ENABLED = 'clerk/category/enabled';
    const XML_PATH_CATEGORY_CONTENT = 'clerk/category/content';
    const XML_PATH_PRODUCT_ENABLED = 'clerk/product/enabled';
    const XML_PATH_PRODUCT_CONTENT = 'clerk/product/content';

    public function checkMessages($observer)
    {
        $modules_for_warning = [
            //'Clerk_Clerk' => ['message' => 'This module can interfear with how we inject our instant search.', 'link' => 'https://clerk.io']
        ];

        $modules = Mage::getConfig()->getNode('modules')->children();

        $modules_array = (array)$modules;

        foreach ($modules_array as $name => $module) {

            if (array_key_exists($name, $modules_for_warning)) {

                $notifications = Mage::getSingleton('clerk/notification');
                $notifications->addMessage('<strong style="color:#eb5e00">Warning: </strong>'.$name.' is installed. '.$modules_for_warning[$name]['message'].'.<a target="_blank" href="'.$modules_for_warning[$name]['link'].'"> Read more here</a>');

            }
        }

        return $observer;
    }

    /**
     * The function is run by the observer when a new product is added to the cart.
     *
     * @param Varien_Event_Observer $observer
     */
    public function itemAddedToCart(Varien_Event_Observer $observer)
    {

        if (!Mage::helper('clerk')->getSetting('clerk/powerstep/active')) {
            return;
        }

        $request = $observer->getEvent()->getRequest();

        if ($request->getParam('skip_powerstep', false)) {
            return;
        }

        if (Mage::helper('clerk')->getSetting('clerk/powerstep/type') == 'page') {
            $request->setParam('return_url', Mage::getBaseUrl() . 'checkout/cart/clerk');
        } else {
            //$referer = $request->getHeader('referer'); Adding referer overwrites the default redirect after add to cart.
            //$request->setParam('return_url', $referer); This causes the powerstep state to not clear properly and makes add to cart animations not execute.
            Mage::getSingleton('core/session')->setFirePowerPopup(true);
        }
    }

    /**
     * The function is run by the observer when a new product is added to the cart.
     *
     * @param Varien_Event_Observer $observer
     */
    public function updateBasket(Varien_Event_Observer $observer)
    {

        $store_id = (Mage::app()->getStore()->getStoreId() !== NULL) ? Mage::app()->getStore()->getStoreId() : 1;

        if (Mage::helper('clerk')->getSetting('clerk/general/collect_baskets', $store_id) == '1') {

            $cart_products = Mage::getModel('checkout/cart')->getQuote()->getAllVisibleItems();

            $cart_product_ids = array();

            foreach ($cart_products as $product) {
                if (!in_array($product->getProduct()->getId(), $cart_product_ids)) {
                    $cart_product_ids[] = $product->getProduct()->getId();
                }
            }

            if (count($cart_product_ids) > 0) {
                
                $email = "";

                if(Mage::getSingleton('customer/session')->isLoggedIn()) {
                    $customer = Mage::getSingleton('customer/session')->getCustomer();
                    $email = $customer->getEmail();
                }

                if ($email != "") {
                    $Endpoint = 'https://api.clerk.io/v2/log/basket/set';

                    $data_string = json_encode([
                        'key' => Mage::helper('clerk')->getSetting('clerk/general/publicapikey', $store_id),
                        'products' => $cart_product_ids,
                        'email' => $email]);

                    $curl = curl_init();

                    curl_setopt($curl, CURLOPT_URL, $Endpoint);
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
                    curl_exec($curl);
                    curl_close($curl);
                }
            }
        }

    }


    /**
     * Sync single product
     *
     * @param $observer
     */
    public function syncProduct(Varien_Event_Observer $observer)
    {
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {

                    if (Mage::helper('clerk')->getSetting('clerk/general/realtime_updates', $store->getId()) == '1') {

                        $productId = $observer->getEvent()->getProduct()->getId();

                        // 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - custom fields - start

                        $product = Mage::getModel('clerk/product')->load($productId);

                        if($product->getTypeId() == "simple"){
                            $confParentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
                            $groupParentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId());
                            if(isset($confParentIds)){
                                foreach ($confParentIds as $parentId) {
                                $parent = Mage::getModel('catalog/product')->load($parentId);
                                    Mage::getModel('clerk/communicator')->syncProduct($parent->getId()); 
                                }

                            }
                            if(isset($groupParentIds)){
                                foreach ($groupParentIds as $parentId) {
                                $parent = Mage::getModel('catalog/product')->load($parentId);
                                    Mage::getModel('clerk/communicator')->syncProduct($parent->getId());
                                }
                            }
                        }

                        // 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - custom fields - end
                        Mage::getModel('clerk/communicator')->syncProduct($productId);

                    }

                }
            }
        }
    }

    /**
     * Sync single product
     *
     * @param $observer
     */
    public function deleteProduct(Varien_Event_Observer $observer)
    {
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {

                    if (Mage::helper('clerk')->getSetting('clerk/general/realtime_updates', $store->getId()) == '1') {

                        $productId = $observer->getEvent()->getProduct()->getId();
                        Mage::getModel('clerk/communicator')->removeProduct($productId);

                    }

                }
            }
        }

    }

     /**
     * refund single product
     *
     * @param $observer
     */
    public function refundProduct(Varien_Event_Observer $observer)
    {

        foreach (Mage::app()->getWebsites() as $website) {

            foreach ($website->getGroups() as $group) {

                $stores = $group->getStores();
                foreach ($stores as $store) {

                    if (Mage::helper('clerk')->getSetting('clerk/general/enable_order_return_synchronization', $store->getId()) == '1') {

                        $creditmemo = $observer->getEvent()->getCreditmemo();
                        $order = Mage::getModel('sales/order')->load($creditmemo->getOrderId());
                        $incrementid = $order->getIncrementId();
                    
                        foreach ($creditmemo->getAllItems() as $item) {

                            $product_id = $item->getProductId();
                            $quantity = $item->getQty();

                            if ($product_id && $incrementid && $quantity !=0 ) {
                                Mage::getModel('clerk/communicator')->returnProduct($incrementid, $product_id, $quantity);
                            }

                        }

                    }

                }
            }
        }

    }

    /**
     * Mass sync products
     *
     * @param Varien_Event_Observer $observer
     */
    public function syncProducts(Varien_Event_Observer $observer)
    {
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {

                    if (Mage::helper('clerk')->getSetting('clerk/general/realtime_updates', $store->getId()) == '1') {

                        $productIds = $observer->getEvent()->getProductIds();

                        if (!is_array($productIds)) {
                            $productIds = [$productIds];
                        }

                         // 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - custom fields - start

                         foreach ($productIds as $productId) {
                            $product = Mage::getModel('clerk/product')->load($productId);

                            if($product->getTypeId() == "simple"){
                                $confParentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
                                $groupParentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId());
                                if(isset($confParentIds[0])){
                                    foreach ($confParentIds[0] as $parentId) {
                                    $parent = Mage::getModel('catalog/product')->load($parentId);
                                        Mage::getModel('clerk/communicator')->syncProduct($parent->getId());
                                    }
                                }
                                if(isset($groupParentIds[0])){
                                    foreach ($groupParentIds[0] as $parentId) {
                                    $parent = Mage::getModel('catalog/product')->load($parentId);
                                        Mage::getModel('clerk/communicator')->syncProduct($parent->getId());
                                    }
                                }
                            }
                        }

                        // 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - custom fields - end

                        Mage::getModel('clerk/communicator')->syncProduct($productIds, $observer->getEvent()->getName());

                    }

                }
            }
        }
    }

    /**
     * Sync products on catalogrule save
     *
     * @param Varien_Event_Observer $observer
     */
    public function syncOnCatalogRuleSave(Varien_Event_Observer $observer)
    {
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {

                    if (Mage::helper('clerk')->getSetting('clerk/general/realtime_updates', $store->getId()) == '1') {

                        /** @var Mage_CatalogRule_Model_Rule $catalogRule */
                        $catalogRule = $observer->getEvent()->getRule();
                        if ($catalogRule->getIsActive()) {
                            //Request a resync of everything
                            Mage::getModel('clerk/communicator')->syncAll();
                        }

                    }
                }
            }
        }
    }

    /**
     * Sync everything when image cache is cleared
     *
     * @param Varien_Event_Observer $observer
     */
    public function syncOnCleanCatalogImagesCacheAfter(Varien_Event_Observer $observer)
    {
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {

                    if (Mage::helper('clerk')->getSetting('clerk/general/realtime_updates', $store->getId()) == '1') {
                        Mage::getModel('clerk/communicator')->syncAll();
                    }


                }
            }
        }
    }

    /**
     * Allow the SCP ext configurable product id (cpid) to override
     * the product id sent to clerk. By default, Magento will store
     * two rows in sale_flat_order_item table; one for the simple +
     * one for the associated configurable. SCP doesn't work like this
     * so determine the configurable id from the product options instead.
     *
     * @param Varien_Event_Observer $observer
     */
    public function formatScpOrderItem(Varien_Event_Observer $observer)
    {
            if (!Mage::helper('core')->isModuleEnabled('OrganicInternet_SimpleConfigurableProducts')) {
                return;
            }

            /** @var array $output */
            $output = $observer->getEvent()->getOutput();

            /** @var Mage_Sales_Model_Order_Item $_item */
            $_item = $observer->getEvent()->getItem();

            /** @var array $buyRequest */
            $buyRequest = $_item->getProductOptionByCode('info_buyRequest');

            if ($buyRequest && isset($buyRequest['cpid'])) {
                $output['id'] = (int)$buyRequest['cpid'];
            }
    }

    /**
     * Ensure that we've got a 2column layout if faceted search is enabled
     * @param Varien_Event_Observer $observer
     * @throws Varien_Exception
     */
    public function layoutGenerateBlocksAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Core_Model_Layout $layout */
        $layout = $observer->getEvent()->getLayout();
        /** @var Mage_Core_Controller_Varien_Action $action */
        $action = $observer->getEvent()->getAction();

        if (Mage::getStoreConfigFlag(Clerk_Clerk_Model_Config::XML_PATH_FACETED_SEARCH_ENABLED) && $action->getFullActionName() === 'catalogsearch_result_index') {
            $root = $layout->getBlock('root');
            $root->setTemplate('page/2columns-left.phtml');
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function coreBlockAbstractToHtmlBefore(Varien_Event_Observer $observer)
    {

        if (Mage::getStoreConfigFlag(self::XML_PATH_CATEGORY_ENABLED)) {
            /** @var Mage_Core_Block_Abstract $block */
            $block = $observer->getEvent()->getBlock();
            $layout = $block->getLayout();

            if ($layout && in_array('catalog_category_view', $layout->getUpdate()->getHandles()) && $block->getNameInLayout() === 'product_list') {
                $content = $layout->createBlock('clerk/widget_content');
                $content->setContent(Mage::getStoreConfig(self::XML_PATH_CATEGORY_CONTENT));
                $content->setCategoryId($this->getCategoryId());

                echo $content->toHtml();
            }
        }

        if (Mage::getStoreConfigFlag(self::XML_PATH_PRODUCT_ENABLED)) {
            /** @var Mage_Core_Block_Abstract $block */
            $block = $observer->getEvent()->getBlock();
            $layout = $block->getLayout();

            if ($layout && in_array('catalog_product_view', $layout->getUpdate()->getHandles()) && $block->getNameInLayout() === 'content') {
                $contents = array_map('trim', explode(',', Mage::getStoreConfig(self::XML_PATH_PRODUCT_CONTENT)));

                //Loop contents and append blocks
                foreach ($contents as $content) {
                    $contentBlock = $layout->createBlock('clerk/widget_content');
                    $contentBlock->setContent($content);
                    $contentBlock->setProductId($this->getProductId());

                    $block->append($contentBlock);
                }
            }
        }
    }

    public function getCategoryId()
    {
        $category = Mage::registry('current_category');
        $categoryId = sprintf('category/%s', $category->getId());

        return $categoryId;
    }

    /**
     * Get current product id
     *
     * @return string
     */
    public function getProductId()
    {
        $product = Mage::registry('current_product');
        $productId = sprintf('product/%s', $product->getId());

        return $productId;
    }
}
