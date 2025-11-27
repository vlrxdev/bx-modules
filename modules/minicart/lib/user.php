<?php
namespace Minicart;

use Bitrix\Main\Context;
use Bitrix\Main\Application;

class User
{
    private static $fuserId;
    
    public static function getFuserId(): int
    {
        if (self::$fuserId !== null) {
            return self::$fuserId;
        }
        
        global $USER;
        $context = Context::getCurrent();
        $request = $context->getRequest();
        
        if ($USER->IsAuthorized()) {
            self::$fuserId = (int)$USER->GetID();
            return self::$fuserId;
        }
        
        self::$fuserId = $request->getCookie('MINICART_FUSER_ID');
        
        if (!self::$fuserId) {
            self::$fuserId = self::generateFuserId();
            setcookie('MINICART_FUSER_ID', self::$fuserId, time() + 3600 * 24 * 365, '/');
        }
        
        return (int)self::$fuserId;
    }
    
    private static function generateFuserId(): int
    {
        return crc32(uniqid('minicart_', true) . time() . rand(1000, 9999));
    }
    
    public static function getUserId(): ?int
    {
        global $USER;
        return $USER->IsAuthorized() ? (int)$USER->GetID() : null;
    }
    
    public static function mergeBaskets(int $fromFuserId, int $toUserId): bool
    {
        try {
            $connection = Application::getConnection();
            $sql = "UPDATE minicart_basket 
                    SET USER_ID = ?, FUSER_ID = ?
                    WHERE FUSER_ID = ? AND USER_ID IS NULL";
            $connection->queryExecute($sql, $toUserId, $toUserId, $fromFuserId);
            return true;
        } catch (\Exception $e) {
            AddMessage2Log("Basket merge error: " . $e->getMessage());
            return false;
        }
    }
}
?>
