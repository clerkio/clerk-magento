<?php

class Clerk_Clerk_Block_Widget_Content extends Mage_Core_Block_Template implements Mage_Widget_Block_Interface
{
    const XML_PATH_CART_ENABLED = 'clerk/cart/enabled';
    const XML_PATH_CART_CONTENT = 'clerk/cart/content';

    /**
     * Set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('clerk/widget/content.phtml');
    }

    /**
     * @return string|void
     * @throws Varien_Exception
     */
    protected function _toHtml()
    {
        if ($this->getBlockLocation() === 'cart') {
            if (! Mage::getStoreConfigFlag(self::XML_PATH_CART_ENABLED)) {
                return;
            }
        }

        return parent::_toHtml();
    }

    /**
     * Get attributes for Clerk span
     *
     * @return string
     * @throws Varien_Exception
     */
    public function getSpanAttributes()
    {

        //$filter_powerstep = Mage::getStoreConfigFlag('clerk/powerstep/exclude_duplicates_powerstep');
        $filter_category = Mage::getStoreConfigFlag('clerk/category/exclude_duplicates_category');
        $filter_product = Mage::getStoreConfigFlag('clerk/product/exclude_duplicates_product');
        $filter_cart = Mage::getStoreConfigFlag('clerk/cart/exclude_duplicates_cart');

        static $product_contents = 0;
        static $cart_contents = 0;
        static $category_contents = 0;

        $output = '';
        $spanAttributes = [
            'class' => 'clerk',
            'data-template' => '@' . $this->getContent()
        ];

        if ($this->getProductId()) {
            $value = explode('/', $this->getProductId());
            $productId = false;

            if (isset($value[0]) && isset($value[1]) && $value[0] == 'product') {
                $productId = $value[1];
            }

            if ($productId) {
                $spanAttributes['data-products'] = json_encode([$productId]);
                if($filter_product){
                    $unique_class = "clerk_" . (string)$product_contents;
                    $spanAttributes['class'] = 'clerk ' . $unique_class;
                    if($product_contents > 0){
                        $filter_string = '';
                        for($i = 0; $i < $product_contents; $i++){
                            if($i > 0){
                                $filter_string .= ', ';
                            }
                            $filter_string .= '.clerk_'.strval($i);
                        }
                    $spanAttributes['data-exclude-from'] = $filter_string;
                    }
                }
            }
        }

        if ($this->getCategoryId()) {
            $value = explode('/', $this->getCategoryId());
            $categoryId = false;

            if (isset($value[0]) && isset($value[1]) && $value[0] == 'category') {
                $categoryId = $value[1];
            }

            if ($categoryId) {
                $spanAttributes['data-category'] = $categoryId;
                if($filter_category){
                    $unique_class = "clerk_" . (string)$category_contents;
                    $spanAttributes['class'] = 'clerk ' . $unique_class;
                    if($category_contents > 0){
                        $filter_string = '';
                        for($i = 0; $i < $category_contents; $i++){
                            if($i > 0){
                                $filter_string .= ', ';
                            }
                            $filter_string .= '.clerk_'.strval($i);
                        }
                    $spanAttributes['data-exclude-from'] = $filter_string;
                    }
                }
            }
        }

        if ($this->getBlockLocation() === 'cart') {
            $spanAttributes['data-template'] = '@' . $this->getCartContent();
            $spanAttributes['data-products'] = $this->getCartProducts();
            if($filter_cart){
                $unique_class = "clerk_" . (string)$cart_contents;
                $spanAttributes['class'] = 'clerk ' . $unique_class;
                if($cart_contents > 0){
                    $filter_string = '';
                    for($i = 0; $i < $cart_contents; $i++){
                        if($i > 0){
                            $filter_string .= ', ';
                        }
                        $filter_string .= '.clerk_'.strval($i);
                    }
                $spanAttributes['data-exclude-from'] = $filter_string;
                }
            }
        }

        foreach ($spanAttributes as $attribute => $value) {
            $output .= ' ' . $attribute . '=\'' . $value . '\'';
        }
        $product_contents++;
        $cart_contents++;
        $category_contents++;
        return trim($output);
    }

    /**
     * Get content for cart
     *
     * @return mixed
     */
    public function getCartContent()
    {
        return Mage::getStoreConfig(self::XML_PATH_CART_CONTENT);
    }

    /**
     * Get product IDs from cart
     *
     * @return string
     * @throws Varien_Exception
     */
    public function getCartProducts()
    {

        $cart_products = Mage::getModel('checkout/cart')->getQuote()->getAllVisibleItems();
            $cart_product_ids = array();

            foreach ($cart_products as $product) {
                if (!in_array($product->getProduct()->getId(), $cart_product_ids)) {
                    $cart_product_ids[] = $product->getProduct()->getId();
                }
            }

            $ids = Mage::getSingleton('checkout/cart')->getProductIds();

            $json_string = json_encode(array_values($cart_product_ids));

        return $json_string;
    }
}