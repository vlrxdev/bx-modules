<?php
namespace Minicart;

use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Minicart\Orm\BasketItemTable;

class Basket
{
    const EVENT_BEFORE_ADD = 'OnBeforeBasketAdd';
    const EVENT_AFTER_ADD = 'OnAfterBasketAdd';
    const EVENT_BEFORE_UPDATE = 'OnBeforeBasketUpdate';
    const EVENT_AFTER_UPDATE = 'OnAfterBasketUpdate';
    const EVENT_BEFORE_DELETE = 'OnBeforeBasketDelete';
    const EVENT_AFTER_DELETE = 'OnAfterBasketDelete';
    
    private $fuserId;
    private $userId;
    private $items = null;
    private $isLoaded = false;
    
    public function __construct()
    {
        $this->fuserId = User::getFuserId();
        $this->userId = User::getUserId();
    }
    
    public function addProduct(int $productId, float $quantity = 1, array $properties = []): array
    {
        $event = new Event('minicart', self::EVENT_BEFORE_ADD, [
            'productId' => $productId,
            'quantity' => $quantity,
            'properties' => $properties
        ]);
        $event->send();
        
        foreach ($event->getResults() as $eventResult) {
            if ($eventResult->getType() === EventResult::ERROR) {
                return ['success' => false, 'error' => 'Product addition cancelled'];
            }
        }
        
        try {
            if (!Product::validateProduct($productId)) {
                return ['success' => false, 'error' => 'Product not available'];
            }
            
            $productQuantity = Product::getProductQuantity($productId);
            if ($productQuantity < $quantity) {
                return ['success' => false, 'error' => 'Not enough products in stock'];
            }
            
            $existingItem = $this->findItemByProductId($productId, $properties);
            
            if ($existingItem) {
                $newQuantity = $existingItem['QUANTITY'] + $quantity;
                if ($productQuantity < $newQuantity) {
                    return ['success' => false, 'error' => 'Not enough products in stock'];
                }
                return $this->updateItemQuantity($existingItem['ID'], $newQuantity);
            }
            
            $price = Product::getProductPrice($productId);
            
            if ($price <= 0) {
                return ['success' => false, 'error' => 'Product price not found'];
            }
            
            $result = BasketItemTable::add([
                'FUSER_ID' => $this->fuserId,
                'USER_ID' => $this->userId,
                'PRODUCT_ID' => $productId,
                'QUANTITY' => $quantity,
                'PRICE' => $price,
                'CURRENCY' => 'RUB',
                'DATE_INSERT' => new DateTime(),
                'DATE_UPDATE' => new DateTime(),
                'PROPERTIES' => $properties
            ]);
            
            if ($result->isSuccess()) {
                $this->isLoaded = false;
                $itemId = $result->getId();
                
                $event = new Event('minicart', self::EVENT_AFTER_ADD, [
                    'itemId' => $itemId,
                    'productId' => $productId,
                    'quantity' => $quantity
                ]);
                $event->send();
                
                return [
                    'success' => true,
                    'itemId' => $itemId,
                    'totalQuantity' => $this->getTotalQuantity(),
                    'totalPrice' => $this->getTotalPrice()
                ];
            }
            
            return ['success' => false, 'error' => implode(', ', $result->getErrorMessages())];
            
        } catch (\Exception $e) {
            AddMessage2Log("Basket add error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Internal server error'];
        }
    }
    
    public function updateItemQuantity(int $basketItemId, float $quantity): array
    {
        try {
            if ($quantity <= 0) {
                return $this->removeItem($basketItemId);
            }
            
            $item = $this->getItemById($basketItemId);
            if (!$item) {
                return ['success' => false, 'error' => 'Basket item not found'];
            }
            
            $productQuantity = Product::getProductQuantity($item['PRODUCT_ID']);
            if ($productQuantity < $quantity) {
                return [
                    'success' => false, 
                    'error' => 'Not enough products in stock',
                    'maxQuantity' => $productQuantity
                ];
            }
            
            $event = new Event('minicart', self::EVENT_BEFORE_UPDATE, [
                'itemId' => $basketItemId,
                'quantity' => $quantity
            ]);
            $event->send();
            
            $result = BasketItemTable::update($basketItemId, [
                'QUANTITY' => $quantity
            ]);
            
            if ($result->isSuccess()) {
                $this->isLoaded = false;
                
                $event = new Event('minicart', self::EVENT_AFTER_UPDATE, [
                    'itemId' => $basketItemId,
                    'quantity' => $quantity
                ]);
                $event->send();
                
                return [
                    'success' => true,
                    'totalQuantity' => $this->getTotalQuantity(),
                    'totalPrice' => $this->getTotalPrice()
                ];
            }
            
            return ['success' => false, 'error' => implode(', ', $result->getErrorMessages())];
            
        } catch (\Exception $e) {
            AddMessage2Log("Basket update error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Internal server error'];
        }
    }
    
    public function removeItem(int $basketItemId): array
    {
        try {
            $event = new Event('minicart', self::EVENT_BEFORE_DELETE, [
                'itemId' => $basketItemId
            ]);
            $event->send();
            
            $result = BasketItemTable::delete($basketItemId);
            
            if ($result->isSuccess()) {
                $this->isLoaded = false;
                
                $event = new Event('minicart', self::EVENT_AFTER_DELETE, [
                    'itemId' => $basketItemId
                ]);
                $event->send();
                
                return [
                    'success' => true,
                    'totalQuantity' => $this->getTotalQuantity(),
                    'totalPrice' => $this->getTotalPrice()
                ];
            }
            
            return ['success' => false, 'error' => implode(', ', $result->getErrorMessages())];
            
        } catch (\Exception $e) {
            AddMessage2Log("Basket remove error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Internal server error'];
        }
    }
    
    public function getItems(): array
    {
        $this->loadIfNeeded();
        return $this->items;
    }
    
    public function getTotalQuantity(): float
    {
        $this->loadIfNeeded();
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item['QUANTITY'];
        }
        return $total;
    }
    
    public function getTotalPrice(): float
    {
        $this->loadIfNeeded();
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item['QUANTITY'] * $item['PRICE'];
        }
        return $total;
    }
    
    public function clear(): bool
    {
        try {
            $items = BasketItemTable::getList([
                'filter' => ['FUSER_ID' => $this->fuserId, 'USER_ID' => $this->userId],
                'select' => ['ID']
            ])->fetchAll();
            
            foreach ($items as $item) {
                BasketItemTable::delete($item['ID']);
            }
            
            $this->items = [];
            $this->isLoaded = true;
            return true;
            
        } catch (\Exception $e) {
            AddMessage2Log("Basket clear error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getItemById(int $basketItemId): ?array
    {
        $this->loadIfNeeded();
        foreach ($this->items as $item) {
            if ($item['ID'] == $basketItemId) {
                return $item;
            }
        }
        return null;
    }
    
    private function findItemByProductId(int $productId, array $properties = []): ?array
    {
        $this->loadIfNeeded();
        foreach ($this->items as $item) {
            if ($item['PRODUCT_ID'] === $productId && $item['PROPERTIES'] == $properties) {
                return $item;
            }
        }
        return null;
    }
    
    private function loadIfNeeded(): void
    {
        if (!$this->isLoaded) {
            $this->load();
        }
    }
    
    private function load(): void
    {
        try {
            $this->items = [];
            $result = BasketItemTable::getList([
                'filter' => ['FUSER_ID' => $this->fuserId, 'USER_ID' => $this->userId],
                'order' => ['DATE_INSERT' => 'DESC']
            ]);
            
            while ($row = $result->fetch()) {
                $this->items[] = $row;
            }
            
            $this->isLoaded = true;
            
        } catch (\Exception $e) {
            AddMessage2Log("Basket load error: " . $e->getMessage());
        }
    }
}
?>
