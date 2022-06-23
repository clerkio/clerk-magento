<?php

class Clerk_Clerk_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template
{
    public function _toHtml($className = "notification-global")
    {
        // Let other extensions add messages
        Mage::dispatchEvent('clerk_notifications_before');
        // Get the global notification object
        $messages = Mage::getSingleton('clerk/notification')->getMessages();
        $html = null;
        foreach ($messages as $message) {
            $html .= "<div class='$className'>" . $message . "</div>";
        }
        return $html;
    }
}