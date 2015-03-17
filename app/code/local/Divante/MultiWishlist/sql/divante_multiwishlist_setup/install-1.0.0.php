<?php
/**
 * Created by PhpStorm.
 * User: Marek Kidon
 * Date: 2014-10-15
 * Time: 15:15
 */ 
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->getConnection()->dropForeignKey(
    $installer->getTable('wishlist/wishlist'),
    $installer->getFkName('wishlist/wishlist', 'customer_id', 'customer/entity', 'entity_id')
);

$installer->getConnection()->dropIndex(
    $installer->getTable('wishlist/wishlist'),
    $installer->getIdxName('wishlist/wishlist', array('customer_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
);

$installer->getConnection()->addIndex(
    $installer->getTable('wishlist/wishlist'),
    $installer->getIdxName('wishlist/wishlist', array('customer_id')),
    array('customer_id')
);

$installer->run("
    ALTER TABLE {$installer->getTable('wishlist/wishlist')} ADD `name` VARCHAR(64) NULL DEFAULT NULL;
    ALTER TABLE {$installer->getTable('wishlist/wishlist')} ADD `is_active` TINYINT(1) NOT NULL DEFAULT 0;
");

$installer->getConnection()->addForeignKey(
    $installer->getFkName('wishlist/wishlist', 'customer_id', 'customer/entity', 'entity_id'),
    $installer->getTable('wishlist/wishlist'),
    'customer_id',
    $installer->getTable('customer/entity'),
    'entity_id'
);

$installer->endSetup();