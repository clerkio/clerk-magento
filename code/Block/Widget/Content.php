<?php

class Clerk_Clerk_Block_Widget_Content extends Mage_Core_Block_Template implements Mage_Widget_Block_Interface
{
    const XML_PATH_CART_ENABLED = 'clerk/cart/enabled';
    const XML_PATH_CART_CONTENT = 'clerk/cart/content';
    const XML_PATH_PRODUCT_ENABLED = 'clerk/product/enabled';
    const XML_PATH_PRODUCT_CONTENT = 'clerk/product/content';

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

        if ($this->getBlockLocation() === 'product') {
            if (! Mage::getStoreConfigFlag(self::XML_PATH_PRODUCT_ENABLED)) {
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
        $output = '';
        $spanAttributes = [
            'class' => 'clerk',
            'data-template' => '@' . $this->getContent(),
        ];

        if ($this->getProductId()) {
            $value = explode('/', $this->getProductId());
            $productId = false;

            if (isset($value[0]) && isset($value[1]) && $value[0] == 'product') {
                $productId = $value[1];
            }

            if ($productId) {
                $spanAttributes['data-products'] = json_encode([$productId]);
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
            }
        }

        if ($this->getBlockLocation() === 'cart') {
            $spanAttributes['data-template'] = '@' . $this->getCartContent();
            $spanAttributes['data-products'] = $this->getCartProducts();
        }

        if ($this->getBlockLocation() === 'product') {
            $spanAttributes['data-template'] = '@' . $this->getProductContent();
            $spanAttributes['data-products'] = $this->getCurrentProduct();
        }

        foreach ($spanAttributes as $attribute => $value) {
            $output .= ' ' . $attribute . '=\'' . $value . '\'';
        }

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
     * Get content for product page
     *
     * @return mixed
     */
    public function getProductContent()
    {
        return Mage::getStoreConfig(self::XML_PATH_PRODUCT_CONTENT);
    }

    /**
     * Get product IDs from cart
     *
     * @return string
     * @throws Varien_Exception
     */
    public function getCartProducts()
    {
        $ids = Mage::getSingleton('checkout/cart')->getProductIds();

        return json_encode($ids);
    }

    /**
     * Get current product id
     *
     * @return string
     */
    public function getCurrentProduct()
    {
        return json_encode((array) Mage::registry('current_product')->getId());
    }
}