<?php
namespace Minicart\Orm;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class OrderItemTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'minicart_order_items';
    }
    
    public static function getMap()
    {
        return [
            new Entity\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('MINICART_ORDER_ITEM_ENTITY_ID_FIELD')
            ]),
            
            new Entity\IntegerField('ORDER_ID', [
                'required' => true,
                'title' => Loc::getMessage('MINICART_ORDER_ITEM_ENTITY_ORDER_ID_FIELD')
            ]),
            
            new Entity\IntegerField('PRODUCT_ID', [
                'required' => true,
                'title' => Loc::getMessage('MINICART_ORDER_ITEM_ENTITY_PRODUCT_ID_FIELD')
            ]),
            
            new Entity\StringField('PRODUCT_NAME', [
                'required' => true,
                'title' => Loc::getMessage('MINICART_ORDER_ITEM_ENTITY_PRODUCT_NAME_FIELD')
            ]),
            
            new Entity\FloatField('QUANTITY', [
                'required' => true,
                'title' => Loc::getMessage('MINICART_ORDER_ITEM_ENTITY_QUANTITY_FIELD')
            ]),
            
            new Entity\FloatField('PRICE', [
                'required' => true,
                'title' => Loc::getMessage('MINICART_ORDER_ITEM_ENTITY_PRICE_FIELD')
            ]),
            
            new Entity\FloatField('TOTAL_PRICE', [
                'required' => true,
                'title' => Loc::getMessage('MINICART_ORDER_ITEM_ENTITY_TOTAL_PRICE_FIELD')
            ]),
            
            new Entity\ReferenceField(
                'ORDER',
                '\Minicart\Orm\OrderTable',
                ['=this.ORDER_ID' => 'ref.ID'],
                ['join_type' => 'LEFT']
            ),
        ];
    }
}
?>
