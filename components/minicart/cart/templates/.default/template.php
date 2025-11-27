<?php

CUtil::InitJSCore();
$APPLICATION->AddHeadScript('/local/js/minicart.js');

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var array $arParams */
/** @var array $arResult */
?>
<div class="minicart-cart">
    <h2>Корзина</h2>
    
    <?php if (!empty($arResult['ITEMS'])): ?>
        <div class="cart-items">
            <?php foreach ($arResult['ITEMS'] as $item): ?>
                <div class="cart-item" data-basket-item-id="<?= $item['ID'] ?>">
                    <div class="cart-item-main">
                        <div class="cart-item-info">
                            <h3 class="cart-item-title"><?= htmlspecialcharsbx($item['PRODUCT_NAME']) ?></h3>
                            <div class="cart-item-price">Цена: <?= number_format($item['PRICE'], 0, '', ' ') ?> ₽</div>
                        </div>
                        
                        <div class="cart-item-quantity">
                            <button class="cart-btn cart-btn-dec" 
                                    onclick="minicart.updateQuantity(<?= $item['ID'] ?>, <?= $item['QUANTITY'] - 1 ?>)"
                                    <?= $item['QUANTITY'] <= 1 ? 'disabled' : '' ?>>
                                -
                            </button>
                            <span class="cart-quantity-display"><?= $item['QUANTITY'] ?></span>
                            <button class="cart-btn cart-btn-inc" 
                                    onclick="minicart.updateQuantity(<?= $item['ID'] ?>, <?= $item['QUANTITY'] + 1 ?>)"
                                    <?= $item['QUANTITY'] >= $item['PRODUCT_QUANTITY'] ? 'disabled' : '' ?>>
                                +
                            </button>
                        </div>
                        
                        <div class="cart-item-total">
                            <?= number_format($item['TOTAL_PRICE'], 0, '', ' ') ?> ₽
                        </div>
                        
                        <button class="cart-btn cart-btn-remove" 
                                onclick="minicart.removeItem(<?= $item['ID'] ?>)">
                            ×
                        </button>
                    </div>
                    
                    <?php if ($item['QUANTITY'] > $item['PRODUCT_QUANTITY']): ?>
                        <div class="cart-item-warning">
                            Такого количества нет в наличии. Доступно: <?= $item['PRODUCT_QUANTITY'] ?> шт.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="cart-total">
            <div class="cart-total-row">
                <span>Общее количество:</span>
                <strong><?= $arResult['TOTAL_QUANTITY'] ?> шт.</strong>
            </div>
            <div class="cart-total-row">
                <span>Итого к оплате:</span>
                <strong class="cart-total-price"><?= $arResult['FORMATTED_TOTAL_PRICE'] ?> ₽</strong>
            </div>
        </div>
        
        <div class="cart-actions">
            <button class="btn btn-secondary" onclick="minicart.clearCart()">Очистить корзину</button>
            <a href="/order.php" class="btn btn-primary">Оформить заказ</a>
        </div>
    <?php else: ?>
        <div class="cart-empty">
            <p>Ваша корзина пуста</p>
            <a href="/catalog.php" class="btn btn-primary">Перейти в каталог</a>
        </div>
    <?php endif; ?>
</div>
