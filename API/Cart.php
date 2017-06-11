<?php

namespace Modules\Checkout\API;

use Exception;
use Lightning\Tools\Request;
use Lightning\View\API;
use Modules\Checkout\Model\Discount;
use Modules\Checkout\Model\LineItem;
use Modules\Checkout\Model\Order;
use Modules\Checkout\Model\Product;

class Cart extends API {
    /**
     * Get the cart contents.
     */
    public function get() {
        $cart = Order::loadBySession();
        if ($cart) {
            return ['cart' => [
                'subtotal' => $cart->getSubTotal(),
                'shipping' => $cart->getShipping(),
                'discounts' => [
                    'discounts' => $cart->getDiscountDescriptions(),
                    'total' => $cart->getDiscounts(),
                ],
                'shipping_address' => $cart->requiresShippingAddress(),
                'tax' => $cart->getTax(),
                'items' => $this->formatItemsForAPI($cart),
                'id' => $cart->id,
            ]];
        } else {
            return ['cart' => [
                'subtotal' => 0,
                'shipping' => 0,
                'discounts' => 0,
                'shipping_address' => false,
                'tax' => 0,
                'items' => [],
                'id' => 0,
            ]];
        }
    }

    /**
     * Convert the items into an array for the cart API.
     *
     * @param Order $cart
     *   The order.
     *
     * @return array
     *   An array of line items with option data.
     */
    public function formatItemsForAPI(Order $cart) {
        $api_fields = ['checkout_order_item_id' => 1, 'product_id' => 1, 'qty' => 1, 'options' => 1];
        $output_items = [];
        foreach ($cart->getItems() as $item) {
            /* @var LineItem $item */
            $output = array_intersect_key($item->getData(), $api_fields);
            $output['price'] = $item->getPrice();
            $output['title'] = $item->getProduct()->title;
            $output['options_formatted'] = $item->getHTMLFormattedOptions();
            $output_items[] = $output;
        }

        return $output_items;
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
        $qty = Request::post('qty', Request::TYPE_INT);
        $order_item_id = Request::post('order_item_id', Request::TYPE_INT);
        if (!$cart->hasItem($order_item_id)) {
            throw new Exception('Could not change the quantity.');
        }
        $cart->setItemQty($order_item_id, $qty);
        return $this->get();
    }

    public function postSetQtys() {
        $cart = Order::loadBySession();
        if (empty($cart)) {
            throw new Exception('Invalid Cart. Maybe your session expired? Reload the page and try again.');
        }
        $cart->loadItems();
        $updates = Request::post('items', Request::TYPE_ARRAY);
        foreach ($updates as $update) {
            $order_item_id = intval($update['order_item_id']);
            $qty = intval($update['qty']);
            $cart->setItemQty($order_item_id, $qty);
        }
        return $this->get();
    }

    public function postRemoveItem() {
        $cart = Order::loadBySession();
        if (empty($cart)) {
            throw new Exception('Invalid Cart. Maybe your session expired? Reload the page and try again.');
        }
        $order_item_id = Request::post('order_item_id', Request::TYPE_INT);
        if ($cart->removeItem($order_item_id)) {
            return $this->get();
        } else {
            throw new Exception('Could not remove the item.');
        }
    }

    public function postAddDiscount() {
        $cart = Order::loadBySession();
        if ($discount = Discount::loadByCode(Request::post('discount'))) {
            $added = $cart->addDiscount($discount);
            $cart->save();
            if (!$added) {
                throw new Exception('This discount is already applied.');
            }
            return $this->get();
        }
        throw new Exception('That discount code is not valid.');
    }
}
