<?php
/**
 * Created by PhpStorm.
 * User: Marek Kidon
 * Date: 2014-10-22
 * Time: 13:28
 */
class Divante_MultiWishlist_Block_Wishlist_Item_Column_Actions extends Mage_Wishlist_Block_Customer_Wishlist_Item_Column
{
    /** @var Mage_Wishlist_Model_Resource_Wishlist_Collection|Divante_MultiWishlist_Model_Wishlist[] */
    protected $_wishlistCollection;

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
     * @return int
     */
    public function getCurrentWishlistId()
    {
        return $this->getCurrentWishlist()->getId();
    }

    /**
     * @param int $itemId
     * @return string
     */
    public function getCopyActionUrl($itemId)
    {
        return $this->getUrl('*/*/addToAnotherWishlist', array('item_id' => $itemId));
    }

    /**
     * @param int $itemId
     * @return string
     */
    public function getMoveActionUrl($itemId)
    {
        return $this->getUrl('*/*/moveToAnotherWishlist', array('item_id' => $itemId));
    }

    /**
     * Retrieve column related javascript code
     *
     * @return string
     */
    public function getJs()
    {
        $js = "
            function copyWishlistItem(itemId) {
                var url = '" . $this->getCopyActionUrl('%item%') . "';
                url = url.gsub('%item%', itemId);
                var form = $('wishlist-view-form');
                if (form) {
                    var input = form['copy_wishlist_id[' + itemId + ']'];
                    if (input) {
                        var separator = (url.indexOf('?') >= 0) ? '&' : '?';
                        url += separator + 'wishlist_id' + '=' + encodeURIComponent(input.value);
                    }
                }
                setLocation(url);
            }
        ";

        $js .= "
            function moveWishlistItem(itemId) {
                var url = '" . $this->getMoveActionUrl('%item%') . "';
                url = url.gsub('%item%', itemId);
                var form = $('wishlist-view-form');
                if (form) {
                    var input = form['move_wishlist_id[' + itemId + ']'];
                    if (input) {
                        var separator = (url.indexOf('?') >= 0) ? '&' : '?';
                        url += separator + 'wishlist_id' + '=' + encodeURIComponent(input.value);
                    }
                }
                setLocation(url);
            }
        ";

        $js .= parent::getJs();
        return $js;
    }
}