<?php

namespace Modules\Checkout\API;

use Exception;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\View\API;
use Modules\Checkout\Model\Order;

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
                'tax' => $cart->getTax(),
                'items' => $items,
                'id' => $cart->id,
            ]];
        } else {
            return ['cart' => [
                'subtotal' => 0,
                'shipping' => 0,
                'tax' => 0,
                'items' => [],
                'id' => 0,
            ]];
        }
    }

    public function postAddToCart() {
        $cart = Order::loadOrCreateBySession();
        $item_id = Request::post('product_id', 'int');
        $qty = Request::post('qty', 'int');
        $options = Request::post('options', 'assoc_array');
        $cart->addItem($item_id, $qty, $options);
        return $this->get();
    }

    public function postSetQty() {
        $cart = Order::loadBySession();
        $cart->loadItems();
        $item_id = Request::post('product_id', 'int');
        $qty = Request::post('qty', 'int');
        $options = Request::post('options', 'array');
        if ($cart->setItemQty($item_id, $qty, $options)) {
            return $this->get();
        } else {
            throw new Exception('Could not change the quantity.');
        }
    }

    public function postRemoveItem() {
        $cart = Order::loadBySession();
        $cart->loadItems();
        $item_id = Request::post('product_id', 'int');
        $options = Request::post('options', 'array');
        if ($cart->removeItem($item_id, $options)) {
            return $this->get();
        } else {
            throw new Exception('Could not remove the item.');
        }
    }
}
