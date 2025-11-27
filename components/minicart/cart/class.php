<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Minicart\Basket;
use Minicart\Product;

class MinicartCartComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams)
    {
        $arParams['CACHE_TIME'] = isset($arParams['CACHE_TIME']) ? (int)$arParams['CACHE_TIME'] : 3600;
        return $arParams;
    }
    
    public function executeComponent()
    {
        if (!Loader::includeModule('minicart')) {
            ShowError('Minicart module not installed');
            return;
        }
        
        $this->prepareResult();
        $this->includeComponentTemplate();
    }
    
    private function prepareResult()
    {
        $basket = new Basket();
        $items = $basket->getItems();

        $enrichedItems = [];
        foreach ($items as $item) {
            $productData = Product::getProductData($item['PRODUCT_ID']);
            $enrichedItems[] = array_merge($item, [
                'PRODUCT_NAME' => $productData ? $productData['NAME'] : 'Товар - ' . $item['PRODUCT_ID'],
                'PRODUCT_QUANTITY' => $productData ? $productData['QUANTITY'] : 0,
                'PRODUCT_AVAILABLE' => $productData ? $productData['AVAILABLE'] : false,
                'TOTAL_PRICE' => $item['QUANTITY'] * $item['PRICE']
            ]);
        }
        
        $this->arResult = [
            'ITEMS' => $enrichedItems,
            'TOTAL_QUANTITY' => $basket->getTotalQuantity(),
            'TOTAL_PRICE' => $basket->getTotalPrice(),
            'FORMATTED_TOTAL_PRICE' => number_format($basket->getTotalPrice(), 0, '', ' ')
        ];
    }
}
?>
