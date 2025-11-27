<?php
namespace Minicart\Orm;

use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class OrderTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'minicart_orders';
    }
    
    public static function getMap()
    {
        return [
            new Entity\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('MINICART_ORDER_ENTITY_ID_FIELD')
            ]),
            
            new Entity\IntegerField('USER_ID', [
                'required' => true,
                'title' => Loc::getMessage('MINICART_ORDER_ENTITY_USER_ID_FIELD')
            ]),
            
            new Entity\StringField('STATUS', [
                'required' => true,
                'default_value' => 'NEW',
                'title' => Loc::getMessage('MINICART_ORDER_ENTITY_STATUS_FIELD')
            ]),
            
            new Entity\FloatField('TOTAL_PRICE', [
                'required' => true,
                'title' => Loc::getMessage('MINICART_ORDER_ENTITY_TOTAL_PRICE_FIELD')
            ]),
            
            new Entity\StringField('CURRENCY', [
                'required' => true,
                'default_value' => 'RUB',
                'validation' => function() {
                    return [
                        new Entity\Validator\Length(3, 3)
                    ];
                },
                'title' => Loc::getMessage('MINICART_ORDER_ENTITY_CURRENCY_FIELD')
            ]),
            
            new Entity\StringField('CUSTOMER_NAME', [
                'required' => true,
                'title' => Loc::getMessage('MINICART_ORDER_ENTITY_CUSTOMER_NAME_FIELD')
            ]),
            
            new Entity\StringField('CUSTOMER_PHONE', [
                'required' => true,
                'title' => Loc::getMessage('MINICART_ORDER_ENTITY_CUSTOMER_PHONE_FIELD')
            ]),
            
            new Entity\StringField('CUSTOMER_EMAIL', [
                'title' => Loc::getMessage('MINICART_ORDER_ENTITY_CUSTOMER_EMAIL_FIELD')
            ]),
            
            new Entity\TextField('CUSTOMER_ADDRESS', [
                'title' => Loc::getMessage('MINICART_ORDER_ENTITY_CUSTOMER_ADDRESS_FIELD')
            ]),
            
            new Entity\DatetimeField('DATE_INSERT', [
                'required' => true,
                'default_value' => new Type\DateTime(),
                'title' => Loc::getMessage('MINICART_ORDER_ENTITY_DATE_INSERT_FIELD')
            ]),
            
            new Entity\DatetimeField('DATE_UPDATE', [
                'required' => true,
                'default_value' => new Type\DateTime(),
                'title' => Loc::getMessage('MINICART_ORDER_ENTITY_DATE_UPDATE_FIELD')
            ]),
        ];
    }
}
?>
