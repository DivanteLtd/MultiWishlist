<?php
/**
 * Created by PhpStorm.
 * User: Marek Kidon
 * Date: 23.01.14
 * Time: 15:45
 */ 
class Divante_MultiWishlist_Model_Resource_Wishlist extends Mage_Wishlist_Model_Resource_Wishlist
{
    /**
     * @param Divante_MultiWishlist_Model_Wishlist $object
     * @param $customerId
     * @return $this
     */
    public function loadActiveByCustomer(Divante_MultiWishlist_Model_Wishlist $object, $customerId)
    {
        $read = $this->_getReadAdapter();
        if ($read && !is_null($customerId)) {
            $select = $this->_getLoadSelect($this->_customerIdFieldName, $customerId, $object);
            $select->where('is_active = 1');
            $data = $read->fetchRow($select);

            if ($data) {
                $object->setData($data);
            }
        }

        $this->unserializeFields($object);
        $this->_afterLoad($object);

        return $this;
    }

    /**
     * @param array $itemIds
     * @param int $newWishlistId
     * @return $this
     */
    public function quickMoveItems($itemIds, $newWishlistId)
    {
        $write = $this->_getWriteAdapter();
        $itemIds = implode(',', $itemIds);

        if($write && !empty($itemIds) && $newWishlistId > 0) {
            $write->update(
                $this->getTable('wishlist/item'),
                array('wishlist_id' => $newWishlistId),
                "wishlist_item_id IN({$itemIds})"
            );
        }

        return $this;
    }
}