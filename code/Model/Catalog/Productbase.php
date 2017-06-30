<?php

class Clerk_Clerk_Model_Catalog_Productbase extends Mage_Catalog_Model_Product
{
    public $excludeReason = null;

    /* Returns the age of the product in days */
    public function getAge()
    {
        $createdTime = strtotime($this->getCreatedAt());
        $datediff = time() - $createdTime;

        return (int) floor($datediff / (60 * 60 * 24));
    }

    public function load($id, $field = null)
    {
        $product = parent::load($id, $field);
        $this->setExcludeReason();

        return $product;
    }

    public function setExcludeReason()
    {
        // subclass this method
    }

    public function isExcluded()
    {
        return isset($this->excludeReason);
    }

    /* Return True if Specialprice is set and we are in the Specialprice period */
    public function isSpecialPriceActive()
    {
        $currentDate = Mage::getModel('core/date')->timestamp(time());
        $currentDate = $currentDate - ($currentDate % 86400);

        $specialPrice = $this->getSpecialPrice();
        $specialPriceFrom = $this->getSpecialFromDate();
        $specialPriceTo = $this->getSpecialToDate();

        return isset($specialPrice) && (
                (!$specialPriceFrom || strtotime($specialPriceFrom) <= $currentDate) &&
                (!$specialPriceTo || strtotime($specialPriceTo) >= $currentDate)
            );
    }

    /* Returns array representation of clerk product */
    public function getInfo()
    {
        return array(
            'clerk_data' => $this->getClerkExportData(),
            'exclude' => $this->isExcluded(),
            'exclude_reason' => $this->excludeReason,
            'mage_object' => $this->getData(),
        );
    }

    /* Function for calculating prices based on Magento settings */
    public function getClerkPrice($includeDiscounts = false, $includeTax = false)
    {
        // Does prices entered in the backend include tax
        $pricesIncludeTax = Mage::getStoreConfig(
            Mage_Tax_Model_Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX);

        // Find a base price. We use getFinalPrice if we want to include
        // discounts.
        $price = $this->getPrice();
        if ($includeDiscounts) {
            $price = $this->getFinalPrice();
        }

        // Set price based on Magento product type. If product type is
        // not supported return null. Note that the switch overwrites the
        // $price variable and may set the taxClassId variable used in tax
        // calculation
        switch ($this->getTypeId()) {

            case Mage_Catalog_Model_Product_Type::TYPE_GROUPED:

                // Find product with min price and getClerkPrice from that product.
                $_product = Mage::helper('clerk')->getMinPricedProductFromGroup($this);
                if (isset($_product)) {
                    $_product = Mage::getModel('clerk/product')->load($_product->getId());

                    return $_product->getClerkPrice($includeDiscounts, $includeTax);
                }
                break;

            case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:

                // TODO: How does fixed priced bundels behave?

                // NOTE: Category Rule prices have no effect on bundled
                // products. Also note that bundles have no taxclass in
                // Magento, so taxrate is taken from an item in the bundle.

                // price is finalprice, discounts are included.
                list($price, $_) = $this->getPriceModel()
                    ->getTotalPrices($this, null, null, false);

                // If discounts should not be included. Go ahead and find retail price.
                // We should only run the snippet below when SpecialPrice is active
                // for product. Otherwise our final price is equal to our retail price.
                if (!$includeDiscounts && $this->isSpecialPriceActive()) {
                    $price = $price / $this->getSpecialPrice() * 100;
                }

                // Set taxclass based on first product in bundle
                $selectionCollection = $this->getTypeInstance(true)->getSelectionsCollection(
                    $this->getTypeInstance(true)->getOptionsIds($this), $this);
                $idents = $selectionCollection->getAllIds();
                if (count($idents) > 0) {
                    $taxClassId = Mage::getModel('clerk/product')
                        ->load($idents[0])->getTaxClassId();
                }
                break;
        }

        // Use price, tax param and magento tax settings to add tax if needed.
        if ($pricesIncludeTax && !$includeTax) {
            $price = Mage::helper('tax')->getPrice($this, $price);
        }
        if (!$pricesIncludeTax && $includeTax) {

            // this variable might have been set in switch statement
            if (!isset($taxClassId)) {
                $taxClassId = $this->getTaxClassId();
            }
            $taxCalculation = Mage::getModel('tax/calculation');
            $request = $taxCalculation->getRateRequest();
            $taxRate = $taxCalculation->getRate($request->setProductClassId($taxClassId));
            $price = ($price / 100 * $taxRate) + $price;
        }

        return floatval($price);
    }

    /**
     * Determine if product is on sale
     * @return bool
     */
    public function isOnSale()
    {
        return !Mage::helper('clerk')->floatEq(
            $this->getClerkRetailPrice(),
            $this->getClerkFinalPrice()
        );
    }

    /**
     * @return float
     */
    public function getClerkFinalPrice()
    {
        return $this->getClerkPrice(true, false);
    }

    /**
     * @return float
     */
    public function getClerkFinalPriceInclTax()
    {
        return $this->getClerkPrice(true, true);
    }

    /**
     * @return float
     */
    public function getClerkRetailPrice()
    {
        return $this->getClerkPrice(false, false);
    }

    /**
     * @return float
     */
    public function getClerkRetailPriceInclTax()
    {
        return $this->getClerkPrice(false, true);
    }

    public function getClerkImageUrl()
    {
        try {
            return (string) Mage::helper('catalog/image')
                ->init($this, 'small_image')
                ->resize($this->imageHeight, $this->imageWidth);
        } catch (Exception $e) {
            return (string) Mage::getDesign()
                ->getSkinUrl('images/catalog/product/placeholder/image.jpg',
                    array('_area' => 'frontend'));
        }
    }

    /**
     * Get product manufacturer
     *
     * @return mixed
     */
    public function getManufacturer()
    {
        return Mage::helper('clerk')->getAttributeSafe($this, 'manufacturer');
    }

    /**
     * Determine if product has tier price
     *
     * @return bool
     */
    public function hasTierPrice()
    {
        return count($this->getTierPrice()) > 0;
    }

    /**
     * If a product is on sale, calculate the reduction in percent
     *
     * @return float|int
     */
    public function getDiscountPercent()
    {
        if ($this->isOnSale()) {
            return round((($this->getClerkRetailPrice() - $this->getClerkFinalPrice()) / $this->getClerkRetailPrice()) * 100);
        } else {
            return 0;
        }
    }
}
