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

    protected $user;

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

    public function getUser() {
        if (empty($this->user)) {
            $this->user = User::loadById($this->user_id);;
        }
        return $this->user;
    }

    /**
     * Create a rendered HTML table with the cart contents.
     *
     * @return string
     */
    public function formatContents($formatOptions = []) {
        $contents = '<table width="100%"><tr><td>Qty</td><td>Item</td><td align="right">Amount</td><td align="right">Total</td></tr>';
        $this->loadItems();
        foreach ($this->items as $item) {
            $contents .= '<tr><td>' . $item->qty . '</td>';
            if (!empty($formatOptions['links'])) {
                $title = '<a href="/store/' . $item->getProduct()->url . '">' . $item->getProduct()->title . '</a>';
            } else {
                $title = $item->getProduct()->title;
            }
            $contents .= '<td><strong>' . $title . '</strong>';
            if ($options = $item->getHTMLFormattedOptions()) {
                $contents .= '<br>' . $options;
            }
            $contents .= '</td><td align="right">$' . number_format($item->getProduct()->price, 2) . '</td>';
            $contents .= '<td align="right">$' . number_format($item->getProduct()->price * $item->qty, 2) . '</td></tr>';
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
            $flat_shipping += $item->qty * $item->getAggregateOption('flat_shipping_more');
            $biggest_flat_diff = max($biggest_flat_diff, $item->getAggregateOption('flat_shipping') - $item->getAggregateOption('flat_shipping_more'));
        }
        return $flat_shipping + $biggest_flat_diff;
    }

    public function getSubTotal() {
        $this->loadItems();
        $this->total = 0;
        foreach ($this->items as $item) {
            /* @var LineItem $item */
            $this->total += $item->qty * $item->getPrice();
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

    /**
     * Check if any items in the cart require a shipping address.
     *
     * @return boolean
     *   Whether any items in this cart require a shipping address.
     */
    public function requiresShippingAddress() {
        $this->loadItems();
        $shipping_address = false;
        foreach ($this->items as $item) {
            if ($item->getProduct()->shipping_address == 1) {
                $shipping_address = true;
            }
        }
        return $shipping_address;
    }

    public function getTotal() {
        return $this->getSubTotal() + $this->getShipping() + $this->getDiscounts() + $this->getTax();
    }

    public function hasItem($order_item_id) {
        $this->loadItems();
        return Database::getInstance()->check('checkout_order_item', [
            'order_id' => $this->id,
            'checkout_order_item_id' => $order_item_id,
        ]);
    }

    public function addItem($product, $qty, $options = []) {
        // Make sure the order is saved, so it has an ID.
        if (empty($this->id)) {
            $this->save();
        }

        // Make sure the product is an object.
        if (is_int($product)) {
            $product = Product::loadByID($product);
        }

        $item = [
            'order_id' => $this->id,
            'product_id' => $product->id,
            'options' => !empty($options) ? base64_encode(json_encode($options)) : null
        ];

        if ($map = $product->getMappedOption('qty')) {
            if (!empty($options[$map])) {
                $qty = intval($options[$map]) ?: 1;
            }
        }

        $db = Database::getInstance();
        if ($row = $db->selectRow('checkout_order_item', $item)) {
            // Update existing item by adding qty.
            $db->update('checkout_order_item', [
                'qty' => [
                    'expression' => 'qty + ?',
                    'vars' => [intval($qty)]
                ],
            ], $item);
            return $row['checkout_order_item_id'];
        } else {
            // Insert new checkout item.
            return $db->insert('checkout_order_item', $item + [ 'qty' => $qty ]);
        }
    }

    public function setItemQty($order_item_id, $qty) {
        $this->loadItems();
        return Database::getInstance()->update('checkout_order_item', [
            'qty' => $qty,
        ], [
            'order_id' => $this->id,
            'checkout_order_item_id' => $order_item_id,
        ]);
    }

    public function removeItem($order_item_id) {
        $this->loadItems();
        return Database::getInstance()->delete('checkout_order_item', [
            'order_id' => $this->id,
            'checkout_order_item_id' => $order_item_id,
        ]);
    }

    /**
     * Get a list of line items in this order.
     *
     * @return array[LineItem]
     *   An array of LineItem objects.
     */
    public function getItems() {
        // Load all the items.
        $this->loadItems();

        return $this->items;
    }

    /**
     * Ensures that the items have been loaded.
     */
    public function loadItems() {
        if ($this->items === null) {
            $this->items = LineItem::loadAllByOrderID($this->id);
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

    public function getRequiredFulfillmentHandlers() {
        $this->loadItems();
        $required_handlers = [];
        foreach ($this->items as $i) {
            if ($handler = $i->getAggregateOption('fulfillment')) {
                $required_handlers[] = $handler;
            }
        }
        return array_unique($required_handlers);
    }

    /**
     * Get a list of items that require s specific fulfillment handler.
     *
     * @param string $handler
     *   The name of the handler.
     *
     * @return array
     *   A list of LineItems.
     */
    public function getItemsToFulfillWithHandler($handler) {
        $this->loadItems();
        $items = [];
        foreach ($this->items as $item) {
            /* @var LineItem $item */
            if ($item->getAggregateOption('fulfillment') == $handler) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Mark an order as fulfilled. This will check ot make sure each line item has been fulfilled.
     *
     * @param boolean $force
     *   Whether to set the item as fulfilled, regardless of whether line items are still pending.
     *
     * @return boolean
     *   Whether the order was set.
     */
    public function markFulfilled($force = false) {
        if (!$force) {
            $this->loadItems();
            foreach ($this->items as $item) {
                if ($item->fulfilled == 0) {
                    return false;
                }
            }
        }
        $this->shipped = time();
        $this->save();
        return true;
    }
}
