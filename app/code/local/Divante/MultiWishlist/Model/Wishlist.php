<?php
/**
 * Created by PhpStorm.
 * User: Marek Kidon
 * Date: 22.01.14
 * Time: 16:55
 */

/**
 * Class Divante_MultiWishlist_Model_Wishlist
 *
 * @method Divante_MultiWishlist_Model_Wishlist setName(string $value)
 * @method string getName()
 * @method bool hasName()
 * @method Divante_MultiWishlist_Model_Wishlist setIsActive(int $value)
 * @method int getIsActive()
 * @method Divante_MultiWishlist_Model_Resource_Wishlist _getResource()
 */
class Divante_MultiWishlist_Model_Wishlist extends Mage_Wishlist_Model_Wishlist
{
    /** @var Mage_Wishlist_Model_Resource_Wishlist_Collection|Divante_MultiWishlist_Model_Wishlist[] */
    protected $_wishlistCollection;

    /**
     * @param mixed $customer
     * @param bool $create Create wishlist if don't exists
     * @return Divante_MultiWishlist_Model_Wishlist
     */
    public function loadByCustomer($customer, $create = false)
    {
        if ($customer instanceof Mage_Customer_Model_Customer) {
            $customer = $customer->getId();
        }

        $customer = (int) $customer;
        $this->_getResource()->loadActiveByCustomer($this, $customer);

        if($this->getId()) {
            return $this;
        }

        parent::loadByCustomer($customer, $create);
        $this->setIsActive(1);
        $this->setCustomerId($customer);
        $this->save();
        return $this;
    }

    /**
     * @param $customer
     * @param string $name
     * @return Divante_MultiWishlist_Model_Wishlist
     */
    public function createNew($customer, $name)
    {
        if ($customer instanceof Mage_Customer_Model_Customer) {
            $customer = $customer->getId();
        }
        /** @var Divante_MultiWishlist_Model_Wishlist $newWishlist */
        $newWishlist = Mage::getModel('divante_multiwishlist/wishlist');
        $newWishlist->setCustomerId($customer);
        $newWishlist->setSharingCode($this->_getSharingRandomCode());
        $newWishlist->setName($name);

        return $newWishlist;
    }

    /**
     * @param Mage_Customer_Model_Customer|int $customer
     * @return Mage_Wishlist_Model_Resource_Wishlist_Collection|Divante_MultiWishlist_Model_Wishlist[]
     */
    public static function getCustomerWishlistCollection($customer)
    {
        if ($customer instanceof Mage_Customer_Model_Customer) {
            $customer = $customer->getId();
        }

        /** @var Mage_Wishlist_Model_Resource_Wishlist_Collection $collection */
        $collection = Mage::getModel('divante_multiwishlist/wishlist')->getCollection();
        $collection->addFieldToFilter('customer_id', array('eq' => $customer));

        return $collection;
    }

    public function getWishlistCollection()
    {
        if(is_null($this->_wishlistCollection)) {
            $this->_wishlistCollection = $this->getCustomerWishlistCollection($this->getCustomerId());
        }

        return $this->_wishlistCollection;
    }

    /**
     * @param Divante_MultiWishlist_Model_Wishlist|int $wishlistToMerge
     * @return $this
     */
    public function merge($wishlistToMerge)
    {
        if(!$wishlistToMerge instanceof Divante_MultiWishlist_Model_Wishlist) {
            $wishlistToMerge = Mage::getModel('divante_multiwishlist/wishlist')->load($wishlistToMerge);
        }
        /** @var Mage_Wishlist_Model_Resource_Item_Collection $wishlistToMergeItems */
        $wishlistToMergeItems = $wishlistToMerge->getItemCollection();

        return $this->transferItemsFromAnotherWishlist($wishlistToMergeItems, $wishlistToMerge);
    }

    /**
     * @param array | Mage_Wishlist_Model_Resource_Item_Collection $items
     * @param Divante_MultiWishlist_Model_Wishlist $sourceWishlist
     * @param bool $copyMode
     * @param null $targetWishlist
     * @return $this
     */
    public function transferItemsFromAnotherWishlist($items, Divante_MultiWishlist_Model_Wishlist $sourceWishlist, $copyMode = false, $targetWishlist = null)
    {
        if(is_null($targetWishlist)) {
            $targetWishlist = $this;
        }

        if(!$items instanceof Mage_Wishlist_Model_Resource_Item_Collection) {

            if(empty($items)) {
                Mage::throwException('Nie wybrano produktów do przeniesienia/skopiowania');
            }

            $items = $sourceWishlist->getItemCollection()->addFieldToFilter('wishlist_item_id', array('in' => $items));
        }

        $itemsForQuickMove = array();

        foreach($items as $itemToMerge) {
            /** @var Mage_Wishlist_Model_Item $itemToMerge */
            $item = $targetWishlist->getItemByProductId($itemToMerge->getProductId());

            if(!$item && !$copyMode) {
                $itemsForQuickMove[] = $itemToMerge->getId();
                $targetWishlist->getItemCollection()->addItem($itemToMerge);
            } else {
                $targetWishlist->_addItemFromAnotherWishlist($itemToMerge, $item, !$copyMode);
            }
        }

        if(!$copyMode) {
            $this->_quickMoveItemsFromAnotherWishlist($itemsForQuickMove);
        }

        return $this;
    }

    /**
     * @param Mage_Wishlist_Model_Item $sourceItem
     * @param Mage_Wishlist_Model_Item $destinationItem
     * @param bool $deleteSource
     * @return $this
     */
    protected function _addItemFromAnotherWishlist(Mage_Wishlist_Model_Item $sourceItem, $destinationItem = null, $deleteSource = true)
    {
        if($sourceItem->getWishlistId() == $this->getId()) {
            return $this;
        }

        if(is_null($destinationItem)) {
            $destinationItem = $this->getItemByProductId($sourceItem->getProductId());
        }

        if(!$destinationItem && $deleteSource) {
            return $this->_quickMoveItemsFromAnotherWishlist(array($sourceItem->getId()));
        } elseif(!$destinationItem) {
            /** @var Mage_Wishlist_Model_Item $destinationItem */
            $this->addNewItem($sourceItem->getProduct(), new Varien_Object(array('qty' => $sourceItem->getQty())));
            return $this;
        }

        $destinationItem->setQty($destinationItem->getQty() + $sourceItem->getQty());

        if(!Zend_Validate::is(trim($destinationItem->getDescription()), 'NotEmpty')) {
            $destinationItem->setDescription($sourceItem->getDescription());
        }

        /** @var Varien_Db_Adapter_Pdo_Mysql $transaction */
        $transaction = Mage::getSingleton('core/resource')->getConnection('core_write');

        try {
            $transaction->beginTransaction();

            $destinationItem->save();

            if($deleteSource) {
                $sourceItem->delete();
            }

            $transaction->commit();
        } catch(Exception $e) {
            $transaction->rollback();
            Mage::throwException($e->getMessage());
        }

        return $this;
    }

    /**
     * @param array $itemIds
     * @return $this
     */
    protected function _quickMoveItemsFromAnotherWishlist($itemIds)
    {
        $this->_getResource()->quickMoveItems($itemIds, $this->getId());

        return $this;
    }

    /**
     * @param $productId
     * @return bool|Mage_Wishlist_Model_Item
     */
    public function getItemByProductId($productId)
    {
        foreach($this->getItemCollection() as $item) {
            /** @var Mage_Wishlist_Model_Item $item */
            if($item->getProductId() == $productId) {
                return $item;
            }
        }

        return false;
    }

    /**
     * @param array $itemIds
     * @return $this
     */
    public function deleteItems($itemIds)
    {
        if(empty($itemIds)) {
            return $this;
        }

        /** @var Mage_Wishlist_Model_Resource_Item_Collection $collection */
        $collection = Mage::getModel('wishlist/item')->getCollection();
        $collection->addFieldToFilter('wishlist_item_id', array('in' => $itemIds));
        $collection->walk('delete');

        if($this->getId()) {
            $this->save();
        }

        return $this;
    }
}