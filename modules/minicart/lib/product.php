<?php
namespace Minicart;

use Bitrix\Main\Loader;
use Bitrix\Catalog\ProductTable;
use Bitrix\Iblock\ElementTable;

class Product
{
    public static function getProductData(int $productId): ?array
{
    if (!Loader::includeModule('iblock')) {
        return null;
    }

    $res = CIBlockElement::GetList(
        [],
        ['ID' => $productId, 'ACTIVE' => 'Y'],
        false,
        false,
        ['ID', 'NAME', 'IBLOCK_ID', 'PREVIEW_TEXT', 'DETAIL_PAGE_URL', 'PREVIEW_PICTURE']
    );

    if ($element = $res->Fetch()) {
        $price = 0;
        $quantity = 0;

        $propRes = CIBlockElement::GetProperty($element['IBLOCK_ID'], $productId, [], ['CODE' => 'PRICE']);
        if ($prop = $propRes->Fetch()) {
            $price = (float)$prop['VALUE'];
        }

        $propRes = CIBlockElement::GetProperty($element['IBLOCK_ID'], $productId, [], ['CODE' => 'QUANTITY']);
        if ($prop = $propRes->Fetch()) {
            $quantity = (float)$prop['VALUE'];
        }

        return [
            'ID' => (int)$element['ID'],
            'NAME' => $element['NAME'],
            'IBLOCK_ID' => (int)$element['IBLOCK_ID'],
            'PREVIEW_TEXT' => $element['PREVIEW_TEXT'],
            'DETAIL_PAGE_URL' => $element['DETAIL_PAGE_URL'],
            'PREVIEW_PICTURE' => $element['PREVIEW_PICTURE'],
            'PRICE' => $price,
            'QUANTITY' => $quantity,
            'AVAILABLE' => $quantity > 0,
            'CURRENCY' => 'RUB',
            'PROPERTIES' => []
        ];
    }

    return null;
}
private static function getProductPropertiesReliable(int $productId, int $iblockId = null): array
{
    $properties = [];
    
    try {
        if ($iblockId === null) {
            $element = ElementTable::getList([
                'filter' => ['ID' => $productId],
                'select' => ['IBLOCK_ID']
            ])->fetch();
            $iblockId = $element ? $element['IBLOCK_ID'] : 2;
        }
        $dbProps = \CIBlockElement::GetProperty($iblockId, $productId);
        while ($prop = $dbProps->Fetch()) {
            if ($prop['VALUE'] !== false && $prop['VALUE'] !== '' && $prop['VALUE'] !== null) {
                $properties[$prop['CODE']] = $prop['VALUE'];
            }
        }
    } catch (\Exception $e) {
        error_log("Reliable properties error: " . $e->getMessage());
    }
    
    return $properties;
}

private static function getProductPriceReliable(int $productId, array $properties = [], int $iblockId = null): float
{

    if (isset($properties['PRICE']) && is_numeric($properties['PRICE']) && $properties['PRICE'] > 0) {
        return (float)$properties['PRICE'];
    }

    try {
        if ($iblockId === null) {
            $element = ElementTable::getList([
                'filter' => ['ID' => $productId],
                'select' => ['IBLOCK_ID']
            ])->fetch();
            $iblockId = $element ? $element['IBLOCK_ID'] : 2;
        }
        
        $dbRes = \CIBlockElement::GetProperty($iblockId, $productId, [], ['CODE' => 'PRICE']);
        if ($arProp = $dbRes->Fetch()) {
            if (is_numeric($arProp['VALUE']) && $arProp['VALUE'] > 0) {
                return (float)$arProp['VALUE'];
            }
        }
    } catch (\Exception $e) {
        error_log("Direct price property error: " . $e->getMessage());
    }

    if (Loader::includeModule('catalog')) {
        try {
            $price = \CCatalogProduct::GetOptimalPrice($productId, 1);
            if ($price && isset($price['RESULT_PRICE']['DISCOUNT_PRICE']) && $price['RESULT_PRICE']['DISCOUNT_PRICE'] > 0) {
                return (float)$price['RESULT_PRICE']['DISCOUNT_PRICE'];
            }

            $basePrice = \CPrice::GetBasePrice($productId);
            if ($basePrice && $basePrice['PRICE'] > 0) {
                return (float)$basePrice['PRICE'];
            }
        } catch (\Exception $e) {
            error_log("Catalog price error: " . $e->getMessage());
        }
    }
    return 0.0;
}

private static function getProductQuantityReliable(int $productId, array $properties = [], int $iblockId = null): float
{

    if (isset($properties['QUANTITY']) && is_numeric($properties['QUANTITY'])) {
        return (float)$properties['QUANTITY'];
    }

    try {
        if ($iblockId === null) {
            $element = ElementTable::getList([
                'filter' => ['ID' => $productId],
                'select' => ['IBLOCK_ID']
            ])->fetch();
            $iblockId = $element ? $element['IBLOCK_ID'] : 2;
        }
        
        $dbRes = \CIBlockElement::GetProperty($iblockId, $productId, [], ['CODE' => 'QUANTITY']);
        if ($arProp = $dbRes->Fetch()) {
            if (is_numeric($arProp['VALUE'])) {
                return (float)$arProp['VALUE'];
            }
        }
    } catch (\Exception $e) {
        error_log("Direct quantity property error: " . $e->getMessage());
    }

    if (Loader::includeModule('catalog')) {
        try {
            $product = ProductTable::getById($productId)->fetch();
            if ($product && isset($product['QUANTITY']) && is_numeric($product['QUANTITY'])) {
                return (float)$product['QUANTITY'];
            }
        } catch (\Exception $e) {
            error_log("Catalog quantity error: " . $e->getMessage());
        }
    }

    return 0.0;
}

    public static function getProductAvailable(int $productId, float $quantity = null): bool
    {
        if ($quantity === null) {
            $quantity = self::getProductQuantityReliable($productId);
        }
        return $quantity > 0;
    }
    
    public static function updateProductQuantity(int $productId, float $quantity): bool
    {
        if (!Loader::includeModule('iblock')) {
            return false;
        }
        
        try {
            \CIBlockElement::SetPropertyValues($productId, 2, $quantity, 'QUANTITY');

            if (Loader::includeModule('catalog')) {
                \CCatalogProduct::Update($productId, [
                    'QUANTITY' => $quantity,
                    'AVAILABLE' => $quantity > 0 ? 'Y' : 'N'
                ]);
            }
            
            return true;
            
        } catch (\Exception $e) {
            error_log("Product quantity update error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function getProductsByIblock(int $iblockId = 2, int $limit = 50): array
{
    if (!Loader::includeModule('iblock')) {
        return [];
    }
    
    try {
        $products = [];

        $res = \CIBlockElement::GetList(
            ['SORT' => 'ASC', 'ID' => 'DESC'],
            [
                'IBLOCK_ID' => $iblockId, 
                'ACTIVE' => 'Y',
                '!PROPERTY_PRICE' => false,
                '!PROPERTY_QUANTITY' => false
            ],
            false,
            ['nTopCount' => $limit],
            [
                'ID', 
                'NAME', 
                'PREVIEW_TEXT', 
                'DETAIL_PAGE_URL', 
                'PREVIEW_PICTURE',
                'PROPERTY_PRICE',
                'PROPERTY_QUANTITY'
            ]
        );
        
        while ($product = $res->Fetch()) {
            $productData = [
                'ID' => (int)$product['ID'],
                'NAME' => $product['NAME'],
                'PRICE' => (float)$product['PROPERTY_PRICE_VALUE'],
                'QUANTITY' => (int)$product['PROPERTY_QUANTITY_VALUE'],
                'AVAILABLE' => ((int)$product['PROPERTY_QUANTITY_VALUE'] > 0),
                'PREVIEW_TEXT' => $product['PREVIEW_TEXT'],
                'DETAIL_PAGE_URL' => $product['DETAIL_PAGE_URL'],
                'PREVIEW_PICTURE' => $product['PREVIEW_PICTURE']
            ];
            
            $products[] = $productData;
        }
        
        return $products;
        
    } catch (\Exception $e) {
        error_log("Products list error: " . $e->getMessage());
        return [];
    }
}
    
    public static function validateProduct(int $productId): bool
    {
        $productData = self::getProductData($productId);
        return $productData !== null && $productData['AVAILABLE'];
    }
}
?>
