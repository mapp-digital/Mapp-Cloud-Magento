<?php


use Magento\Catalog\Model\Category;
use Magento\TestFramework\Helper\Bootstrap;

$category = Bootstrap::getObjectManager()->create(Category::class);
$category->isObjectNew(true);
$category->setId(
    100
)->setCreatedAt(
    '2020-10-10 10:10:10'
)->setName(
    'Mapp Category'
)->setParentId(
    2
)->setPath(
    '1/2/100'
)->setLevel(
    2
)->setAvailableSortBy(
    ['position', 'name']
)->setDefaultSortBy(
    'name'
)->setIsActive(
    true
)->setPosition(
    1
)->save();
