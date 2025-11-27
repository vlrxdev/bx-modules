<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var array $arParams */
/** @var array $arResult */
?>
<div class="minicart-catalog">
    <h2>Каталог одежды</h2>
    
    <?php if (!empty($arResult['ERROR'])): ?>
        <div class="catalog-error">
            <h3>Ошибка</h3>
            <p><?= htmlspecialcharsbx($arResult['ERROR']) ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($arResult['HAS_PRODUCTS']): ?>
        <p>Найдено товаров: <?= $arResult['TOTAL_PRODUCTS'] ?></p>
        
        <div class="catalog-grid">
            <?php foreach ($arResult['PRODUCTS'] as $product): ?>
                <div class="catalog-item" data-product-id="<?= $product['ID'] ?>">
                    <?php if ($product['PREVIEW_PICTURE']): ?>
                        <div class="catalog-item-image">
                            <?php
                            $picture = CFile::GetFileArray($product['PREVIEW_PICTURE']);
                            if ($picture): ?>
                                <img src="<?= $picture['SRC'] ?>" alt="<?= htmlspecialcharsbx($product['NAME']) ?>">
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="catalog-item-info">
                        <h3 class="catalog-item-title"><?= htmlspecialcharsbx($product['NAME']) ?></h3>
                        
                        <?php if ($product['PREVIEW_TEXT']): ?>
                            <p class="catalog-item-description"><?= htmlspecialcharsbx($product['PREVIEW_TEXT']) ?></p>
                        <?php endif; ?>
                        
                        <div class="catalog-item-meta">
                            <div class="catalog-item-price">
                                <?= number_format($product['PRICE'], 0, '', ' ') ?> ₽
                            </div>
                            <div class="catalog-item-stock <?= $product['QUANTITY'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                                <?= $product['QUANTITY'] > 0 ? "В наличии: {$product['QUANTITY']} шт." : "Нет в наличии" ?>
                            </div>
                        </div>
                        
                        <div class="catalog-item-actions">
                            <button class="btn btn-primary add-to-cart" 
                                    <?= !$product['AVAILABLE'] ? 'disabled' : '' ?>>
                                <?= $product['AVAILABLE'] ? 'В корзину' : 'Нет в наличии' ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="catalog-empty">
            <p>Товары не найдены</p>
        </div>
    <?php endif; ?>
</div>
