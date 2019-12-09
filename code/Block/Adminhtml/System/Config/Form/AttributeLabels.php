<?php

class Clerk_Clerk_Block_Adminhtml_System_Config_Form_AttributeLabels extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '<div class="hor-scroll"><table class="dynamic-grid" id="attribute-labels-table" cellspacing="0"><tbody>';

        $html .= '<tr>';
        $html .= '<th>' . $this->__('Default') .'</th>';
        $html .= '<th>' . $this->getStore()->getName() . '</th>';
        $html .= '<th>' . $this->__('Sort Order') . '</th>';
        $html .= '</tr>';

        //Loop over specified facet attributes
        $attributes = $this->getConfiguredAttributes();

        $values = json_decode($element->getValue(), true);

        foreach (explode(',', $attributes) as $attribute) {
            $attribute = str_replace(' ','',$attribute);

            $value = isset($values[$attribute]) ? $values[$attribute] : $attribute;

            $html .= '<tr>';
            $html .= '<td><input id="' . $element->getHtmlId() . '_orig" class="input-text disabled" value="' . $attribute . '" type="text" readonly></td>';
            $html .= '<td><input id="' . $element->getHtmlId() . '" class="input-text" name="' . $element->getName() . '[' . $attribute . '][label]" value="' . $value['label'] . '' . '" type="text"></td>';
            $html .= '<td><input id="' . $element->getHtmlId() . '" class="input-text" name="' . $element->getName() . '[' . $attribute . '][sort_order]" value="' . $value['sort_order'] . '' . '" type="text"></td>';
            $html .= '</tr>';

        }

        $html .= '</tbody></table></div>';

        $html .= $element->getAfterElementHtml();

        return $html;
    }

    /**
     * Get configured facet attributes
     *
     * @return mixed
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function getConfiguredAttributes()
    {
        return Mage::getStoreConfig(Clerk_Clerk_Model_Config::XML_PATH_FACETED_SEARCH_ATTRIBUTES, $this->getStore());
    }

    /**
     * Get store being configured
     *
     * @return Mage_Core_Model_Store
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function getStore()
    {
        return Mage::app()->getStore(Mage::getSingleton('adminhtml/config_data')->getStore());
    }
}