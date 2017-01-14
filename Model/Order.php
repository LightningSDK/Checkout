<?php

namespace Modules\Checkout\Model;

use Lightning\Model\Object;
use Lightning\Model\User;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Session;
use Lightning\View\HTMLEditor\Markup;
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
class OrderOverridable extends Object {
    const TABLE = 'checkout_order';
    const PRIMARY_KEY = 'order_id';

    protected $__json_encoded_fields = [
        'details',
        'discounts' => [
            'type' => 'array',
        ]
    ];

    /**
     * List of actual discount objects.
     *
     * @var array
     */
    protected $discounts = [];

    protected $discountValue = 0;

    /**
     * List of items in the cart.
     *
     * @var array
     */
    protected $items;

    protected $shippingAddress;

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

    public function getShippingAddress() {
        if (empty($this->shippingAddress)) {
            $this->shippingAddress = Address::loadByID($this->shipping_address);
        }
        return $this->shippingAddress;
    }

    public function formatContents() {
        $contents = '<table width="100%"><tr><td>Qty</td><td>Item</td><td align="right">Amount</td><td align="right">Total</td></tr>';
        foreach ($this->getItems() as $item) {
            $contents .= '<tr><td>' . $item['qty'] . '</td>';
            $contents .= '<td><strong>' . $item['title'] . '</strong>';
            if (!empty($item['description'])) {
                $contents .= '<br>' . $item['description'];
            }
            if (!empty($item['options_formatted'])) {
                $contents .= '<br>' . $item['options_formatted'];
            }
            $contents .= '</td><td align="right">$' . number_format($item['price'], 2) . '</td>';
            $contents .= '<td align="right">$' . number_format($item['price'] * $item['qty'], 2) . '</td></tr>';
        }
        $contents .= '<tr><td colspan="2"></td><td align="right">Shipping</td><td align="right">$' . number_format($this->getShipping(), 2) . '</td>';
        $contents .= '<tr><td colspan="2"></td><td align="right">Total</td><td align="right">$' . number_format($this->getTotal(), 2) . '</td></tr></table>';
        return $contents;
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

    public function getDiscounts() {
        $this->discountValue = 0;
        // TODO: This can be optimized.
        $discounts = Discount::loadAll(['code' => ['IN', $this->__data['discounts']]]);
        foreach ($discounts as $d) {
            $this->discountValue += $d->getAmount($this);
        }

        return $this->discountValue;
    }

    public function getDiscountDescriptions() {
        return $this->__data['discounts'];
    }

    public function requiresShippingAddress() {
        $this->loadItems();
        $shipping_address = false;
        foreach ($this->items as $item) {
            if ($item['shipping_address'] == 1) {
                $shipping_address = true;
            }
        }
        return $shipping_address;
    }

    public function getTotal() {
        return $this->getSubTotal() + $this->getShipping() + $this->getDiscounts() + $this->getTax();
    }

    public function hasItem($product_id, $options = '') {
        return Database::getInstance()->check('checkout_order_item', [
            'order_id' => $this->id,
            'product_id' => $product_id,
            'options' => !empty($options) ? (is_array($options) ? base64_encode(json_encode($options)) : $options) : null
        ]);
    }

    public function addItem($product_id, $qty, $options = []) {
        $db = Database::getInstance();
        $item = [
            'order_id' => $this->id,
            'product_id' => $product_id,
            'options' => !empty($options) ? base64_encode(json_encode($options)) : null
        ];
        if ($db->selectRow('checkout_order_item', $item)) {
            // Update existing item by adding qty.
            $db->update('checkout_order_item', [
                'qty' => [
                    'expression' => 'qty + ?',
                    'vars' => [intval($qty)]
                ],
            ], $item);
        } else {
            // Insert new checkout item.
            $db->insert('checkout_order_item', $item + [ 'qty' => $qty ]);
        }
    }

    public function setItemQty($product_id, $qty, $options = '') {
        return Database::getInstance()->update('checkout_order_item', [
            'qty' => $qty,
        ], [
            'order_id' => $this->id,
            'product_id' => $product_id,
            'options' => !empty($options) ? $options : null
        ]);
    }

    public function removeItem($product_id, $options = '') {
        return Database::getInstance()->delete('checkout_order_item', [
            'order_id' => $this->id,
            'product_id' => $product_id,
            'options' => !empty($options) ? $options : null
        ]);
    }

    public function getItems() {
        // Load all the items.
        $this->loadItems();

        // Load all the products.
        $product_ids = [];
        foreach ($this->items as $item) {
            $product_ids[$item['product_id']] = $item['product_id'];
        }
        $products = Product::loadAll(['product_id' => ['IN', $product_ids]], [], '', true);

        foreach ($this->items as &$item) {
            // Save a reference to the product.
            // TODO: $items should be converted into objects.
            $item['product'] = $products[$item['product_id']];

            // Get the HTML formatted options.
            if (!empty($item['product']->options->option_formatting_user)) {
                $item['options_formatted'] = Markup::render(
                    $item['product']->options->option_formatting_user,
                    json_decode(base64_decode($item['order_item_options']), true) ?: []
                );
            } else {
                $options = json_decode(base64_decode($item['order_item_options']), true) ?: [];
                $output = '';
                foreach ($options as $option => $value) {
                    $output .= $option . ': <strong>' . $value . '</strong> ';
                }
                if (!empty($output)) {
                    $item['options_formatted'] = $output;
                }
            }
        }

        return $this->items;
    }

    public function loadItems() {
        if ($this->items === null) {
            $this->items = Database::getInstance()->selectAllQuery([
                'select' => [
                    'qty', 'checkout_product.*', 'checkout_order_item.options',
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

        // If there is a user id and a mailing list ID, then subscribe the user to the list.
        if (!empty($this->user_id && $list = Configuration::get('modules.checkout.lists.any'))) {
            User::loadById($this->user_id)->subscribe($list);
        }

        return $payment;
    }

    /**
     * Add a new discount to the order.
     *
     * @param Discount $discount
     *
     * @return boolean
     */
    public function addDiscount($discount) {
        if (!in_array($discount->code, $this->__data['discounts'])) {
            $this->__data['discounts'][] = $discount->code;
            $this->discounts[$discount->code] = $discount;
            return true;
        }
        return false;
    }
}
