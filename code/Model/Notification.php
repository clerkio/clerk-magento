<?php

class Clerk_Clerk_Model_Notification extends Varien_object
{
    protected $messages = [ ];

    public function getMessages()
    {
        return $this->messages;
    }

    public function setMessages($messages)
    {
        $this->messages = $messages;
        return $this;
    }

    public function addMessage($message)
    {
        $this->messages[] = $message;
        return $this;
    }
}