<?php

class Clerk_Clerk_Block_Adminhtml_Info extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '<h3>Notice</h3>';
        $html .= '<p>The settings for the clerk.io module, must be set at storeview scope.</p>';
        $html .= '<p>Scope is chosen in top left corner.</p>';

        return $html;
    }
}
