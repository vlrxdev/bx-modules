<?php
namespace Minicart\Orm;

use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class BasketItemTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'minicart_basket';
    }
    
    public static function getMap()
    {
        return [
            new Entity\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('MINICART_ENTITY_ID_FIELD')
            ]),
            
            new Entity\IntegerField('FUSER_ID', [
                'required' => true,
                'title' => Loc::getMessage('MINICART_ENTITY_FUSER_ID_FIELD')
            ]),
            
            new Entity\IntegerField('USER_ID', [
                'title' => Loc::getMessage('MINICART_ENTITY_USER_ID_FIELD')
            ]),
            
            new Entity\IntegerField('PRODUCT_ID', [
                'required' => true,
                'title' => Loc::getMessage('MINICART_ENTITY_PRODUCT_ID_FIELD')
            ]),
            
            new Entity\FloatField('QUANTITY', [
                'required' => true,
                'title' => Loc::getMessage('MINICART_ENTITY_QUANTITY_FIELD')
            ]),
            
            new Entity\FloatField('PRICE', [
                'required' => true,
                'title' => Loc::getMessage('MINICART_ENTITY_PRICE_FIELD')
            ]),
            
            new Entity\StringField('CURRENCY', [
                'required' => true,
                'validation' => function() {
                    return [
                        new Entity\Validator\Length(3, 3)
                    ];
                },
                'title' => Loc::getMessage('MINICART_ENTITY_CURRENCY_FIELD')
            ]),
            
            new Entity\DatetimeField('DATE_INSERT', [
                'required' => true,
                'default_value' => new Type\DateTime(),
                'title' => Loc::getMessage('MINICART_ENTITY_DATE_INSERT_FIELD')
            ]),
            
            new Entity\DatetimeField('DATE_UPDATE', [
                'required' => true,
                'default_value' => new Type\DateTime(),
                'title' => Loc::getMessage('MINICART_ENTITY_DATE_UPDATE_FIELD')
            ]),
            
            new Entity\TextField('PROPERTIES', [
                'serialized' => true,
                'title' => Loc::getMessage('MINICART_ENTITY_PROPERTIES_FIELD')
            ]),
        ];
    }
    
    public static function onBeforeAdd(Entity\Event $event)
    {
        $result = new Entity\EventResult();
        $result->modifyFields([
            'DATE_UPDATE' => new Type\DateTime()
        ]);
        return $result;
    }
    
    public static function onBeforeUpdate(Entity\Event $event)
    {
        $result = new Entity\EventResult();
        $result->modifyFields([
            'DATE_UPDATE' => new Type\DateTime()
        ]);
        return $result;
    }
}
?>
