/**
 * Форма заказа (менеджер/администратор): динамическое добавление товарных позиций.
 * Данные моделей передаются из Blade в window.ORDER_FORM.
 */
(function () {
    'use strict';

    var config = window.ORDER_FORM || {};
    var products = config.products || [];
    var mode = config.mode === 'edit' ? 'edit' : 'create';
    var initialItems = config.initialItems || [];

    var tbody = document.getElementById('order-items');
    var addButton = document.getElementById('add-order-item');
    var totalLabel = document.getElementById('order-total');

    if (!tbody || !addButton) {
        return;
    }

    var money = new Intl.NumberFormat('ru-RU');
    var rowCounter = 0;

    function productById(id) {
        for (var i = 0; i < products.length; i += 1) {
            if (String(products[i].id) === String(id)) {
                return products[i];
            }
        }

        return null;
    }

    function formatMoney(value) {
        return money.format(Math.round(Number(value) * 100) / 100) + ' ₽';
    }

    function recalcTotals() {
        var total = 0;

        Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function (row) {
            var product = productById(row.dataset.productId);
            var quantity = Number(row.querySelector('.js-quantity').value) || 0;
            var price = product ? Number(product.price) : 0;
            var line = price * quantity;

            row.querySelector('.js-price').textContent = formatMoney(price);
            row.querySelector('.js-line-total').textContent = formatMoney(line);
            total += line;
        });

        if (totalLabel) {
            totalLabel.textContent = formatMoney(total);
        }
    }

    function reindex() {
        Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function (row, index) {
            row.querySelector('.js-product').name = 'items[' + index + '][product_id]';
            row.querySelector('.js-size').name = 'items[' + index + '][size]';
            row.querySelector('.js-quantity').name = 'items[' + index + '][quantity]';
        });
    }

    function fillSizeOptions(row, product, selectedSize) {
        var sizeSelect = row.querySelector('.js-size');
        sizeSelect.innerHTML = '';

        if (!product) {
            sizeSelect.appendChild(new Option('Выберите модель', ''));
            return;
        }

        var usable = product.sizes.filter(function (size) {
            return mode === 'edit' ? true : Number(size.quantity) > 0;
        });

        if (usable.length === 0) {
            sizeSelect.appendChild(new Option('Нет доступных размеров', ''));
            return;
        }

        var hasSelected = false;

        usable.forEach(function (size) {
            var option = new Option(size.label + ' — доступно ' + size.quantity + ' шт.', size.value);
            var selected = selectedSize !== null && String(size.value) === String(selectedSize);

            if (selected) {
                option.selected = true;
                hasSelected = true;
            }

            sizeSelect.appendChild(option);
        });

        if (selectedSize !== null && !hasSelected) {
            var current = new Option(String(selectedSize) + ' — размер в текущем заказе', selectedSize);
            current.selected = true;
            sizeSelect.insertBefore(current, sizeSelect.firstChild);
        }

        if (!hasSelected && sizeSelect.options.length > 0) {
            sizeSelect.options[0].selected = true;
        }
    }

    function createRow(item) {
        rowCounter += 1;

        var row = document.createElement('tr');
        var productId = item && item.product_id !== undefined && item.product_id !== null ? String(item.product_id) : '';

        if (!productId && products.length > 0) {
            productId = String(products[0].id);
        }

        row.dataset.productId = productId;

        // Модель
        var cellProduct = document.createElement('td');
        var productSelect = document.createElement('select');
        productSelect.className = 'js-product';

        products.forEach(function (product) {
            var option = new Option(product.name + ' (' + product.brand + ')', product.id);
            productSelect.appendChild(option);
        });

        if (productId && !productById(productId) && item && item.name) {
            productSelect.appendChild(new Option(item.name, productId));
        }

        productSelect.value = productId;
        cellProduct.appendChild(productSelect);
        row.appendChild(cellProduct);

        // Размер
        var cellSize = document.createElement('td');
        var sizeSelect = document.createElement('select');
        sizeSelect.className = 'js-size';
        cellSize.appendChild(sizeSelect);
        row.appendChild(cellSize);
        fillSizeOptions(row, productById(productId), item ? item.size : null);

        // Количество
        var cellQuantity = document.createElement('td');
        var quantityInput = document.createElement('input');
        quantityInput.type = 'number';
        quantityInput.className = 'js-quantity';
        quantityInput.min = '1';
        quantityInput.max = '999';
        quantityInput.value = item && item.quantity ? String(item.quantity) : '1';
        cellQuantity.appendChild(quantityInput);
        row.appendChild(cellQuantity);

        // Цена за единицу
        var cellPrice = document.createElement('td');
        var priceLabel = document.createElement('span');
        priceLabel.className = 'js-price order-row-price';
        cellPrice.appendChild(priceLabel);
        row.appendChild(cellPrice);

        // Сумма
        var cellLineTotal = document.createElement('td');
        var lineTotalLabel = document.createElement('span');
        lineTotalLabel.className = 'js-line-total order-row-price';
        cellLineTotal.appendChild(lineTotalLabel);
        row.appendChild(cellLineTotal);

        // Удалить
        var cellActions = document.createElement('td');
        var removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'btn btn-danger btn-small';
        removeButton.textContent = 'Удалить';
        cellActions.appendChild(removeButton);
        row.appendChild(cellActions);

        productSelect.addEventListener('change', function () {
            row.dataset.productId = productSelect.value;
            fillSizeOptions(row, productById(productSelect.value), null);
            recalcTotals();
        });

        quantityInput.addEventListener('input', recalcTotals);

        removeButton.addEventListener('click', function () {
            row.parentNode.removeChild(row);
            reindex();
            recalcTotals();
        });

        tbody.appendChild(row);
        reindex();
        recalcTotals();
    }

    addButton.addEventListener('click', function () {
        createRow(null);
    });

    if (initialItems.length > 0) {
        initialItems.forEach(function (item) {
            createRow(item);
        });
    } else {
        createRow(null);
    }

    recalcTotals();
})();