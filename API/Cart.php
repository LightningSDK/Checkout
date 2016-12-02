<?php

namespace Modules\Checkout\API;

use Exception;
use Lightning\Tools\Request;
use Lightning\View\API;
use Modules\Checkout\Model\Order;
use Modules\Checkout\Model\Product;

class Cart extends API {
    /**
     * Get the cart contents.
     */
    public function get() {
        $cart = Order::loadBySession();
        if ($cart) {
            $items = $cart->getItems();
            return ['cart' => [
                'subtotal' => $cart->getSubTotal(),
                'shipping' => $cart->getShipping(),
                'shipping_address' => $cart->requiresShippingAddress(),
                'tax' => $cart->getTax(),
                'items' => $items,
                'id' => $cart->id,
            ]];
        } else {
            return ['cart' => [
                'subtotal' => 0,
                'shipping' => 0,
                'shipping_address' => false,
                'tax' => 0,
                'items' => [],
                'id' => 0,
            ]];
        }
    }

    public function getProduct() {
        $product_id = Request::get('product_id', Request::TYPE_INT);
        if (empty($product_id)) {
            throw new Exception('Invalid Product ID');
        }
        $product = Product::loadByID($product_id);
        if (empty($product)) {
            throw new Exception('Invalid Product');
        }

        return [
            'amount' => $product->price,
        ];
    }

    public function postAddToCart() {
        $cart = Order::loadOrCreateBySession();
        $item_id = Request::post('product_id', Request::TYPE_INT);
        $qty = Request::post('qty', Request::TYPE_INT);
        $options = Request::post('options', Request::TYPE_ASSOC_ARRAY);
        $item = Product::loadByID($item_id);

        // Make sure the product was loaded.
        if (empty($item)) {
            throw new Exception('Invalid product selected.');
        }

        // If there are missing options, we need to show the options form.
        if (!$item->optionsSatisfied($options)) {
            return [
                'form' => $item->getPopupOptionsForm(),
                'options' => $item->options,
                'base_price' => $item->price,
            ];
        }
        $cart->addItem($item_id, $qty, $options);
        return $this->get();
    }

    public function postSetQty() {
        $cart = Order::loadBySession();
        if (empty($cart)) {
            throw new Exception('Invalid Cart. Maybe your session expired? Reload the page and try again.');
        }
        $cart->loadItems();
        $item_id = Request::post('product_id', Request::TYPE_INT);
        $qty = Request::post('qty', Request::TYPE_INT);
        $options = Request::post('options');
        if (!$cart->hasItem($item_id, $options)) {
            throw new Exception('Could not change the quantity.');
        }
        $cart->setItemQty($item_id, $qty, $options);
        return $this->get();
    }

    public function postSetQtys() {
        $cart = Order::loadBySession();
        if (empty($cart)) {
            throw new Exception('Invalid Cart. Maybe your session expired? Reload the page and try again.');
        }
        $cart->loadItems();
        $updates = Request::post('items');
        foreach ($updates as $update) {
            $item_id = intval($update['product_id']);
            $qty = intval($update['qty']);
            $options = $update['options'];
            $cart->setItemQty($item_id, $qty, $options);
        }
        return $this->get();
    }

    public function postRemoveItem() {
        $cart = Order::loadBySession();
        $cart->loadItems();
        $item_id = Request::post('product_id', Request::TYPE_INT);
        $options = Request::post('options');
        if ($cart->removeItem($item_id, $options)) {
            return $this->get();
        } else {
            throw new Exception('Could not remove the item.');
        }
    }
}
