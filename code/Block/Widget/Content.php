<?php

class Clerk_Clerk_Block_Widget_Content extends Mage_Core_Block_Template implements Mage_Widget_Block_Interface
{
    /**
     * Set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('clerk/widget/content.phtml');
    }

    /**
     * Get attributes for Clerk span
     *
     * @return string
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

        foreach ($spanAttributes as $attribute => $value) {
            $output .= ' ' . $attribute . '=\'' . $value . '\'';
        }

        return trim($output);
    }
}