<?php

namespace Modules\Checkout\Model;

use Lightning\Model\Object;
use Lightning\Tools\Database;
use Lightning\Tools\Session;
use Lightning\Tools\ClientUser;

/**
 * Class Order
 * @package Modules\Checkout\Model
 *
 * @parameter integer $id
 * @parameter integer $order_id
 * @parameter integer $status
 * @parameter integer $time
 * @parameter integer $paid
 * @parameter integer $shipped
 * @parameter integer shipping_address
 * @parameter string $gateway_id
 * @parameter integer $tax
 * @parameter integer $shipping
 * @parameter integer $total
 * @parameter string $details
 */
class Order extends Object {
    const TABLE = 'checkout_order';
    const PRIMARY_KEY = 'order_id';

    protected $__json_encoded_fields = ['details'];

    /**
     * List of items in the cart.
     *
     * @var array
     */
    protected $items;

    /**
     * @return Order
     */
    public static function loadBySession($order_id = null) {
        // TODO: Check if there are multiple orders without payments, and merge them
        // (user_id and session_id)
        $criteria = [
            'session_id' => Session::getInstance()->id,
            'locked' => 0,
        ];
        if (!empty($order_id)) {
            $criteria['order_id'] = $order_id;
        }
        $data = Database::getInstance()->selectRow(static::TABLE, $criteria);
        if ($data) {
            return new static($data);
        } else {
            return null;
        }
    }

    /**
     * @return Order
     */
    public static function loadOrCreateBySession() {
        if ($order = self::loadBySession()) {
            return $order;
        } else {
            $data = [
                'user_id' => ClientUser::getInstance()->id,
                'session_id' => Session::getInstance()->id,
                'time' => time(),
            ];
            $data['order_id'] = Database::getInstance()->insert(static::TABLE, $data);
            return new static($data);
        }
    }

    public function getTax() {
        return 0;
    }

    public function getShipping() {
        $this->loadItems();
        $max = null;
        $flat_shipping = 0;
        $biggest_flat_diff = 0;
        foreach ($this->items as $key => $item) {
            $flat_shipping += $item['qty'] * $item['flat_shipping_more'];
            $biggest_flat_diff = max($biggest_flat_diff, $item['flat_shipping'] - $item['flat_shipping_more']);
        }
        return $flat_shipping + $biggest_flat_diff;
    }

    public function getSubTotal() {
        $this->loadItems();
        $this->total = 0;
        foreach ($this->items as $item) {
            $this->total += $item['qty'] * $item['price'];
        }
        return $this->total;
    }

    public function getTotal() {
        return $this->getSubTotal() + $this->getShipping() + $this->getTax();
    }

    public function addItem($product_id, $qty, $options = []) {
        $db = Database::getInstance();
        if ($db->selectRow('checkout_order_item', [
            'order_id' => $this->id,
            'product_id' => $product_id,
            'options' => json_encode($options)
        ])) {
            // Update existing item by adding qty.
            $db->update('checkout_order_item', [
                'qty' => [
                    'expression' => 'qty + ?',
                    'vars' => [intval($qty)]
                ],
            ], [
                'order_id' => $this->id,
                'product_id' => $product_id,
                'options' => json_encode($options)
            ]);
        } else {
            // Insert new checkout item.
            $db->insert('checkout_order_item', [
                'order_id' => $this->id,
                'product_id' => $product_id,
                'qty' => $qty,
                'options' => json_encode($options)
            ]);
        }
    }

    public function setItemQty($product_id, $qty, $options = []) {
        return Database::getInstance()->update('checkout_order_item', [
            'qty' => $qty,
        ], [
            'order_id' => $this->id,
            'product_id' => $product_id,
            'options' => json_encode($options)
        ]);
    }

    public function removeItem($product_id, $options = []) {
        return Database::getInstance()->delete('checkout_order_item', [
            'order_id' => $this->id,
            'product_id' => $product_id,
            'options' => json_encode($options)
        ]);
    }

    public function getItems() {
        $this->loadItems();
        return $this->items;
    }

    public function loadItems() {
        if ($this->items === null) {
            $this->items = Database::getInstance()->selectAllQuery([
                'select' => [
                    'qty', 'checkout_product.*'
                ],
                'from' => 'checkout_order_item',
                'join' => [
                    'left_join' => 'checkout_product',
                    'using' => 'product_id',
                ],
                'where' => ['order_id' => $this->id]
            ]);
        }
    }

    public function addPayment($amount, $currency, $gateway_id = null, $data = []) {
        if (empty($this->id)) {
            $this->save();
        }

        $payment = new Payment([
            'amount' => $amount,
            'currency' => $currency,
            'gateway_id' => $gateway_id,
            'order_id' => $this->id,
        ] + $data);
        $payment->save();

        // Get the total payments received and determine whether to mark this order saved.
        $paid = Database::getInstance()->selectFieldQuery([
            'select' => ['paid' => ['expression' => 'SUM(amount)']],
            'from' => 'checkout_payment',
            'where' => ['order_id' => $this->id],
        ], 'paid');

        // Now that a payment is received, the order is locked.
        $this->locked = 1;

        // If all payments are received, then set the time paid.
        if ($paid >= $this->total) {
            $this->paid = time();
            $this->save();
        }

        return $payment;
    }
}
