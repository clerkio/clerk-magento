<?php

class Clerk_Clerk_Block_Adminhtml_Widget_Store extends Mage_Adminhtml_Block_Widget
{
    /**
     * Prepare element HTML
     *
     * @param Varien_Data_Form_Element_Abstract $element Form Element
     * @return Varien_Data_Form_Element_Abstract
     */
    public function prepareElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $uniqId = Mage::helper('core')->uniqHash($element->getId());
        $contentUrl = $this->getUrl('*/clerk_widget/content');
        $parametersUrl = $this->getUrl('*/clerk_widget/parameters');

        $elements = $this->getLayout()->createBlock('clerk/adminhtml_widget_store_script')
            ->setElement($element)
            ->setTranslationHelper($this->getTranslationHelper())
            ->setConfig($this->getConfig())
            ->setFieldsetId($this->getFieldsetId())
            ->setContentUrl($contentUrl)
            ->setParametersUrl($parametersUrl)
            ->setUniqId($uniqId);
        ;

        $element->setData('after_element_html', $elements->toHtml());

        return $element;
    }
}