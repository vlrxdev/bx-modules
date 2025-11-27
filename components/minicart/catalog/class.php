<?php



if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Minicart\Product;

class MinicartCatalogComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams)
    {
        $arParams['IBLOCK_ID'] = isset($arParams['IBLOCK_ID']) ? (int)$arParams['IBLOCK_ID'] : 2;
        $arParams['PAGE_SIZE'] = isset($arParams['PAGE_SIZE']) ? (int)$arParams['PAGE_SIZE'] : 12;
        $arParams['CACHE_TIME'] = isset($arParams['CACHE_TIME']) ? (int)$arParams['CACHE_TIME'] : 3600;
        return $arParams;
    }
    
    public function executeComponent()
    {
        if (!Loader::includeModule('minicart')) {
            $this->arResult['ERROR'] = 'minicart не подключен';
            $this->includeComponentTemplate();
            return;
        }
        
        if (!Loader::includeModule('iblock')) {
            $this->arResult['ERROR'] = 'iblock не подключен';
            $this->includeComponentTemplate();
            return;
        }
        
        $this->prepareResult();
        $this->includeComponentTemplate();
    }
    
    private function prepareResult()
    {
        try {
            $this->arResult['PRODUCTS'] = Product::getProductsByIblock(
                $this->arParams['IBLOCK_ID'],
                $this->arParams['PAGE_SIZE']
            );
            
            $this->arResult['TOTAL_PRODUCTS'] = count($this->arResult['PRODUCTS']);
            $this->arResult['HAS_PRODUCTS'] = !empty($this->arResult['PRODUCTS']);
            
        } catch (Exception $e) {
            $this->arResult['ERROR'] = 'Ошибка при загрузке товаров: ' . $e->getMessage();
            $this->arResult['PRODUCTS'] = [];
            $this->arResult['HAS_PRODUCTS'] = false;
        }
    }
}
?>
