<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
define('STOP_STATISTICS', true);
define('BX_SESSION_ID_CHANGE', false);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

header('Content-Type: application/json; charset=utf-8');
$result = ['success' => false, 'error' => ''];

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }

    if (!check_bitrix_sessid()) {
        throw new Exception('Invalid session token');
    }

    $allowedActions = ['add', 'update', 'remove', 'clear', 'get'];
    $action = trim($_POST['action'] ?? '');
    
    if (!in_array($action, $allowedActions)) {
        throw new Exception('Invalid action');
    }

    if (!CModule::IncludeModule('minicart')) {
        throw new Exception('Cart module not available');
    }

    $productId = 0;
    $basketItemId = 0;
    $quantity = 1.0;

    switch ($action) {
        case 'add':
            $productId = (int)($_POST['product_id'] ?? 0);
            if ($productId <= 0) {
                throw new Exception('Invalid product ID');
            }
            $quantity = (float)($_POST['quantity'] ?? 1);
            if ($quantity <= 0 || $quantity > 1000) {
                throw new Exception('Invalid quantity');
            }
            break;

        case 'update':
            $basketItemId = (int)($_POST['basket_item_id'] ?? 0);
            if ($basketItemId <= 0) {
                throw new Exception('Invalid basket item ID');
            }
            $quantity = (float)($_POST['quantity'] ?? 1);
            if ($quantity < 0 || $quantity > 1000) {
                throw new Exception('Invalid quantity');
            }
            break;

        case 'remove':
            $basketItemId = (int)($_POST['basket_item_id'] ?? 0);
            if ($basketItemId <= 0) {
                throw new Exception('Invalid basket item ID');
            }
            break;
            
        case 'get':
            break;
            
        case 'clear':
            break;
    }

    $basket = new Minicart\Basket();
    
    switch ($action) {
        case 'add':
            $result = $basket->addProduct($productId, $quantity);
            break;
            
        case 'update':
            $result = $basket->updateItemQuantity($basketItemId, $quantity);
            break;
            
        case 'remove':
            $result = $basket->removeItem($basketItemId);
            break;
            
        case 'clear':
            $result = ['success' => $basket->clear()];
            break;
            
        case 'get':
            $result = $basket->getBasket();
            break;
    }

    if (!isset($result['success'])) {
        $result['success'] = true;
    }

} catch (Exception $e) {
    $result = [
        'success' => false, 
        'error' => $e->getMessage(),
        'error_code' => 'SYSTEM_ERROR'
    ];
}
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>
