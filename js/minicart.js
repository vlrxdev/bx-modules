if (typeof window.minicart === 'undefined') {
    window.minicart = {
        addProduct: function(productId, quantity, event) {
            console.log('🔍 DEBUG addProduct called:');
            console.log('Product ID:', productId);
            console.log('Quantity:', quantity);
            console.log('Sessid:', BX.bitrix_sessid());
            
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            BX.ajax({
                url: '/local/ajax/minicart.php',
                data: {
                    action: 'add',
                    product_id: productId,
                    quantity: quantity,
                    sessid: BX.bitrix_sessid()
                },
                method: 'POST',
                dataType: 'json',
                onsuccess: function(result) {
                    console.log('✅ AJAX Response:', result);
                    
                    if (result.success) {
                        if (typeof BX !== 'undefined' && BX.UI && BX.UI.Notification) {
                            BX.UI.Notification.Center.notify({
                                content: 'Товар добавлен в корзину!',
                                autoHideDelay: 3000
                            });
                        } else {
                            alert('Товар добавлен в корзину!');
                        }
                        minicart.updateMiniCart(result);
                    } else {
                        console.error('AJAX Error:', result.error);
                        alert('Ошибка: ' + result.error);
                    }
                },
                onfailure: function(error) {
                    console.error('AJAX Request Failed:', error);
                    alert('Ошибка сети. Попробуйте еще раз.');
                }
            });
        },
        updateQuantity: function(basketItemId, quantity) {
            BX.ajax({
                url: '/local/ajax/minicart.php',
                data: {
                    action: 'update',
                    basket_item_id: basketItemId,
                    quantity: quantity,
                    sessid: BX.bitrix_sessid()
                },
                method: 'POST',
                dataType: 'json',
                onsuccess: function(result) {
                    if (result.success) {
                        minicart.refreshCart();
                    } else {
                        alert('Ошибка: ' + result.error);
                        if (result.maxQuantity !== undefined) {
                            minicart.updateQuantityUI(basketItemId, result.maxQuantity);
                        }
                    }
                }
            });
        },
        removeItem: function(basketItemId) {
            if (confirm('Вы уверены, что хотите удалить товар из корзины?')) {
                BX.ajax({
                    url: '/local/ajax/minicart.php',
                    data: {
                        action: 'remove',
                        basket_item_id: basketItemId,
                        sessid: BX.bitrix_sessid()
                    },
                    method: 'POST',
                    dataType: 'json',
                    onsuccess: function(result) {
                        if (result.success) {
                            minicart.refreshCart();
                        } else {
                            alert('Ошибка: ' + result.error);
                        }
                    }
                });
            }
        },
        clearCart: function() {
            if (confirm('Вы уверены, что хотите очистить корзину?')) {
                BX.ajax({
                    url: '/local/ajax/minicart.php',
                    data: {
                        action: 'clear',
                        sessid: BX.bitrix_sessid()
                    },
                    method: 'POST',
                    dataType: 'json',
                    onsuccess: function(result) {
                        if (result.success) {
                            minicart.refreshCart();
                        } else {
                            alert('Ошибка: ' + result.error);
                        }
                    }
                });
            }
        },
        refreshCart: function() {
            BX.ajax({
                url: window.location.href,
                data: {
                    'minicart_ajax': 'Y'
                },
                method: 'GET',
                onsuccess: function(html) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    var newCart = doc.querySelector('.minicart-cart');
                    if (newCart) {
                        var currentCart = document.querySelector('.minicart-cart');
                        currentCart.parentNode.replaceChild(newCart, currentCart);
                        minicart.initEvents();
                    }
                }
            });
        },
        updateMiniCart: function(result) {
            var cartCounter = document.querySelector('.minicart-counter');
            if (cartCounter) {
                cartCounter.textContent = result.totalQuantity || 0;
                cartCounter.style.display = result.totalQuantity > 0 ? 'inline' : 'none';
            }
        },
        updateQuantityUI: function(basketItemId, maxQuantity) {
            var itemElement = document.querySelector('.cart-item[data-basket-item-id="' + basketItemId + '"]');
            if (itemElement) {
                var quantityDisplay = itemElement.querySelector('.cart-quantity-display');
                var incBtn = itemElement.querySelector('.cart-btn-inc');
                if (quantityDisplay) quantityDisplay.textContent = maxQuantity;
                if (incBtn) incBtn.disabled = true;
                
                var warning = itemElement.querySelector('.cart-item-warning');
                if (!warning) {
                    warning = document.createElement('div');
                    warning.className = 'cart-item-warning';
                    warning.innerHTML = 'Достигнут лимит доступного количества';
                    itemElement.appendChild(warning);
                }
            }
        },

        initEvents: function() {
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('add-to-cart')) {
                    var productId = parseInt(e.target.closest('.catalog-item').dataset.productId);
                    minicart.addProduct(productId, 1, e);
                }
            });
        }
    };
}

document.addEventListener('DOMContentLoaded', function() {
    minicart.initEvents();
});
