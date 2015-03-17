<?php
/**
 * Created by PhpStorm.
 * User: Marek Kidon
 * Date: 2014-10-22
 * Time: 11:11
 */
class Divante_MultiWishlist_Block_Actions extends Mage_Core_Block_Template
{
    /** @var Mage_Wishlist_Model_Resource_Wishlist_Collection|Divante_MultiWishlist_Model_Wishlist[] */
    protected $_wishlistCollection;

    /**
     * @return string
     */
    public function getChangeWishlistActionUrl()
    {
        return $this->getUrl('*/*/changeWishlist');
    }

    public function getWishlistCollection()
    {
        if(is_null($this->_wishlistCollection)) {
            $this->_wishlistCollection = $this->getCurrentWishlist()->getWishlistCollection();
        }

        return $this->_wishlistCollection;
    }

    /**
     * @return Divante_MultiWishlist_Model_Wishlist
     */
    public function getCurrentWishlist()
    {
        return Mage::registry('wishlist');
    }

    /**
     * @return int|null
     */
    public function getCustomerId()
    {
        return Mage::getSingleton('customer/session')->getCustomerId();
    }

    /**
     * @return string
     */
    public function getCreateNewWishlistActionUrl()
    {
        return $this->getUrl('*/*/createNew');
    }

    /**
     * @return string
     */
    public function getChangeNameActionUrl()
    {
        return $this->getUrl('*/*/changeName');
    }

    /**
     * @return string
     */
    public function getDeleteWishlistUrl()
    {
        return $this->getUrl('*/*/deleteWishlist');
    }

    /**
     * @return int
     */
    public function getCurrentWishlistId()
    {
        return $this->getCurrentWishlist()->getId();
    }

    /**
     * @return string
     */
    public function getCurrentWishlistName()
    {
        return $this->getCurrentWishlist()->getName();
    }

    public function getMergeActionUrl()
    {
        return $this->getUrl('*/*/mergeWishlist');
    }
}