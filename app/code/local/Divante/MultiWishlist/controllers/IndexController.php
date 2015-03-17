<?php
/**
 * Created by PhpStorm.
 * User: Marek Kidon
 * Date: 23.01.14
 * Time: 12:36
 */

require_once 'Mage/Wishlist/controllers/IndexController.php';


class Divante_MultiWishlist_IndexController extends Mage_Wishlist_IndexController
{
    /**
     * @return Divante_MultiWishlist_Model_Wishlist|bool
     */
    protected function _getWishlist()
    {
        $wishlist = Mage::registry('wishlist');
        if ($wishlist) {
            return $wishlist;
        }

        try {
            $wishlist = Mage::getModel('wishlist/wishlist')
                ->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer(), true);
            Mage::register('wishlist', $wishlist);
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('wishlist/session')->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::getSingleton('wishlist/session')->addException($e,
                Mage::helper('wishlist')->__('Cannot create wishlist.')
            );
            return false;
        }

        return $wishlist;
    }

    /**
     * @return Mage_Wishlist_Model_Session
     */
    protected function _getWishlistSession()
    {
        return Mage::getSingleton('wishlist/session');
    }

    /**
     * @return Mage_Customer_Model_Session
     */
    protected function _getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * @param Divante_MultiWishlist_Model_Wishlist $wishlist
     * @return bool
     */
    protected function _validateAccess(Divante_MultiWishlist_Model_Wishlist $wishlist)
    {
        return $this->_getCustomerSession()->getCustomerId() == $wishlist->getCustomerId();
    }

    public function changeNameAction()
    {
        if($this->getRequest()->isPost() && $this->_validateAccess($this->_getWishlist())) {
            $newName = $this->getRequest()->getPost('wishlist_name');

            if(Zend_Validate::is(trim($newName), 'NotEmpty')) {
                try {
                    $this->_getWishlist()->setName($newName)->save();
                    $this->_getWishlistSession()->addSuccess('Nazwa schowka została zapisana poprawnie.');
                } catch(Exception $e) {
                    Mage::logException($e);
                    $this->_getWishlistSession()->addError('Wystąpił błąd podczas zapisu nowej nazwy schowka.');
                }
            } else {
                $this->_getWishlistSession()->addError('Nazwa schowka nie może być pusta.');
            }
        }

        return $this->_redirect('*/*/index');
    }

    public function deleteWishlistAction()
    {
        if($this->_validateAccess($this->_getWishlist())) {
            $this->_getWishlist()->delete();
        } else {
            $this->_getWishlistSession()->addError('Nie posiadasz wystarczających uprawnień aby usunąć ten schowek.');
        }

        return $this->_redirect('*/*/index');
    }

    public function changeWishlistAction()
    {
        $wishlistId = $this->getRequest()->getParam('wishlist_id');
        /** @var Divante_MultiWishlist_Model_Wishlist $wishlist */
        $wishlist = Mage::getModel('divante_multiwishlist/wishlist');
        $wishlist->load($wishlistId);

        if($this->_validateAccess($wishlist)) {
            $this->_getWishlist()->setIsActive(0);
            $wishlist->setIsActive(1);
            try {
                $this->_getWishlist()->save();
                $wishlist->save();
                $this->_getWishlistSession()->addSuccess('Zmieniono schowek.');
            } catch(Exception $e){
                Mage::logException($e);
                $this->_getWishlistSession()->addError('Wystąpił błąd podczas zmiany schowka.');
            }
        } else {
            $this->_getWishlistSession()->addError('Wybrany schowek nie istnieje lub nie posiadasz wystarczających uprawnień do jego przeglądania.');
        }

        return $this->_redirect('*/*/index');
    }

    public function createNewAction()
    {
        $name = $this->getRequest()->getParam('wishlist_name');

        if(Zend_Validate::is(trim($name), 'NotEmpty')) {

            try {
                /** @var Divante_MultiWishlist_Model_Wishlist $newWishlist */
                $newWishlist = Mage::getModel('divante_multiwishlist/wishlist')->createNew($this->_getCustomerSession()->getId(), $name);
                $newWishlist->setIsActive(1);
                $newWishlist->save();

                $this->_getWishlist()->setIsActive(0);
                $this->_getWishlist()->save();
                $this->_getWishlistSession()->addSuccess('Nowy schowek został utworzony.');
            } catch(Exception $e) {
                Mage::logException($e);
                $this->_getWishlistSession()->addError('Nie udało się utworzyć nowego schowka.');
            }

        } else {
            $this->_getWishlistSession()->addError('Nazwa schowka nie może być pusta.');
        }

        return $this->_redirect('*/*/index');
    }

    public function mergeWishlistAction()
    {
        $wishlistToMergeId = $this->getRequest()->getPost('wishlist_id');
        /** @var Divante_MultiWishlist_Model_Wishlist $wishlistToMerge */
        $wishlistToMerge = Mage::getModel('divante_multiwishlist/wishlist')->load($wishlistToMergeId);

        if($wishlistToMerge->getId() && $this->_validateAccess($wishlistToMerge)) {
            try {
                $wishlistToMerge->setIsActive(1);
                $wishlistToMerge->merge($this->_getWishlist());
                $wishlistToMerge->save();
                $this->_getWishlist()->delete();
                $this->_getWishlistSession()->addSuccess($this->__('Schowki poprawnie połączone.'));
            } catch(Exception $e) {
                Mage::logException($e);
                $this->_getWishlistSession()->addError($this->__('Wystąpił błąd podczas łaczenia schowków.'));
            }
        } else {
            $this->_getWishlistSession()->addError($this->__('Niepoprawne ID schowka.'));
        }

        return $this->_redirect('*/*/index');
    }

    public function addToAnotherWishlistAction()
    {
        $itemIds = explode(',', $this->getRequest()->getParam('item_id'));

        if(!empty($itemIds)) {
            $targetWishlistId = $this->getRequest()->getParam('wishlist_id');
            /** @var Divante_MultiWishlist_Model_Wishlist $targetWishlist */
            $targetWishlist = Mage::getModel('divante_multiwishlist/wishlist')->load($targetWishlistId);

            if($this->_validateAccess($targetWishlist)) {
                try {
                    $targetWishlist->transferItemsFromAnotherWishlist($itemIds, $this->_getWishlist(), true);
                    $this->_getWishlistSession()->addSuccess($this->__('Produkty zostały skopiowane do innego schowka.'));
                } catch(Exception $e) {
                    Mage::logException($e);
                    $this->_getWishlistSession()->addError($this->__('Wystąpił błąd podczas kopiowania produktów do innego schowka.'));
                }
            } else {
                $this->_getWishlistSession()->addError($this->__('Nie posiadasz dostępu do podanego schowka.'));
            }

        } else {
            $this->_getWishlistSession()->addError('Nie wybrano produktów do przeniesienia.');
        }

        return $this->_redirect('*/*/index');
    }

    public function moveToAnotherWishlistAction()
    {
        $itemIds = explode(',', $this->getRequest()->getParam('item_id'));

        if(!empty($itemIds)) {
            $targetWishlistId = $this->getRequest()->getParam('wishlist_id');
            /** @var Divante_MultiWishlist_Model_Wishlist $targetWishlist */
            $targetWishlist = Mage::getModel('divante_multiwishlist/wishlist')->load($targetWishlistId);

            if($this->_validateAccess($targetWishlist)) {
                try {
                    $targetWishlist->transferItemsFromAnotherWishlist($itemIds, $this->_getWishlist());
                    $this->_getWishlistSession()->addSuccess($this->__('Produkty zostały przeniesione do innego schowka.'));
                } catch(Exception $e) {
                    Mage::logException($e);
                    $this->_getWishlistSession()->addError($this->__('Wystąpił błąd podczas przenoszenia produktów do innego schowka.'));
                }
            } else {
                $this->_getWishlistSession()->addError($this->__('Nie posiadasz dostępu do podanego schowka.'));
            }

        } else {
            $this->_getWishlistSession()->addError('Nie wybrano produktów do przeniesienia.');
        }

        return $this->_redirect('*/*/index');
    }
}