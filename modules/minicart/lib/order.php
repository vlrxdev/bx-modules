<?php
namespace Minicart;

use Bitrix\Main\Type\DateTime;
use Minicart\Orm\OrderTable;
use Minicart\Orm\OrderItemTable;

class Order
{
    public static function create(array $orderData, array $basketItems): array
    {
        try {
            if (empty($basketItems)) {
                return ['success' => false, 'error' => 'Basket is empty'];
            }
            
            $totalPrice = 0;
            foreach ($basketItems as $item) {
                $totalPrice += $item['QUANTITY'] * $item['PRICE'];
            }
            $orderResult = OrderTable::add([
                'USER_ID' => User::getUserId() ?: 0,
                'STATUS' => 'NEW',
                'TOTAL_PRICE' => $totalPrice,
                'CURRENCY' => 'RUB',
                'CUSTOMER_NAME' => $orderData['name'],
                'CUSTOMER_PHONE' => $orderData['phone'],
                'CUSTOMER_EMAIL' => $orderData['email'] ?? '',
                'CUSTOMER_ADDRESS' => $orderData['address'] ?? '',
                'DATE_INSERT' => new DateTime(),
                'DATE_UPDATE' => new DateTime()
            ]);
            
            if (!$orderResult->isSuccess()) {
                return ['success' => false, 'error' => implode(', ', $orderResult->getErrorMessages())];
            }
            
            $orderId = $orderResult->getId();
            foreach ($basketItems as $item) {
                $productData = Product::getProductData($item['PRODUCT_ID']);
                $itemResult = OrderItemTable::add([
                    'ORDER_ID' => $orderId,
                    'PRODUCT_ID' => $item['PRODUCT_ID'],
                    'PRODUCT_NAME' => $productData ? $productData['NAME'] : 'Товар #' . $item['PRODUCT_ID'],
                    'QUANTITY' => $item['QUANTITY'],
                    'PRICE' => $item['PRICE'],
                    'TOTAL_PRICE' => $item['QUANTITY'] * $item['PRICE']
                ]);
                
                if (!$itemResult->isSuccess()) {
                    OrderTable::delete($orderId);
                    return ['success' => false, 'error' => 'Failed to add order items'];
                }

                $currentQuantity = Product::getProductQuantity($item['PRODUCT_ID']);
                $newQuantity = $currentQuantity - $item['QUANTITY'];
                if ($newQuantity < 0) $newQuantity = 0;
                
                Product::updateProductQuantity($item['PRODUCT_ID'], $newQuantity);
            }

            $basket = new Basket();
            $basket->clear();
            
            return [
                'success' => true,
                'orderId' => $orderId,
                'totalPrice' => $totalPrice
            ];
            
        } catch (\Exception $e) {
            AddMessage2Log("Order create error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Internal server error'];
        }
    }
    
    public static function getUserOrders(int $userId): array
    {
        try {
            $orders = [];
            $result = OrderTable::getList([
                'filter' => ['USER_ID' => $userId],
                'order' => ['DATE_INSERT' => 'DESC']
            ]);
            
            while ($order = $result->fetch()) {
                $orderItems = self::getOrderItems($order['ID']);
                $order['ITEMS'] = $orderItems;
                $orders[] = $order;
            }
            
            return $orders;
            
        } catch (\Exception $e) {
            AddMessage2Log("Get user orders error: " . $e->getMessage());
            return [];
        }
    }
    
    public static function getOrderItems(int $orderId): array
    {
        try {
            $items = [];
            $result = OrderItemTable::getList([
                'filter' => ['ORDER_ID' => $orderId]
            ]);
            
            while ($item = $result->fetch()) {
                $items[] = $item;
            }
            
            return $items;
            
        } catch (\Exception $e) {
            AddMessage2Log("Get order items error: " . $e->getMessage());
            return [];
        }
    }
}
?>
