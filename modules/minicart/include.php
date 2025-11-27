<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

$autoloadPaths = [
    'Minicart\Orm\BasketItemTable' => 'lib/orm/basketitemtable.php',
    'Minicart\Orm\OrderTable' => 'lib/orm/ordertable.php',
    'Minicart\Orm\OrderItemTable' => 'lib/orm/orderitemtable.php',
    'Minicart\User' => 'lib/user.php',
    'Minicart\Product' => 'lib/product.php',
    'Minicart\Basket' => 'lib/basket.php',
    'Minicart\Order' => 'lib/order.php',
];

foreach ($autoloadPaths as $className => $path) {
    $fullPath = __DIR__ . '/' . $path;
    if (file_exists($fullPath)) {
        require_once $fullPath;
    }
}
AddEventHandler('main', 'OnAfterUserLogin', function($params) {
    if ($params['user_fields']['ID']) {
        $fuserId = Minicart\User::getFuserId();
        $userId = (int)$params['user_fields']['ID'];
        Minicart\User::mergeBaskets($fuserId, $userId);
    }
});
?>
