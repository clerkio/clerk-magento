<?php

class BasicTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        /* You'll have to load Magento app in any test classes in this method */
        $app = Mage::app('default');
    }

    public function testFirstMethod()
    {
	$this->assertEquals(1, 1);
        /*Here goes the assertions for your block first method*/
    }

    public function testSecondMethod()
    {
        /*Here goes the assertions for your block second method*/
    }
}
