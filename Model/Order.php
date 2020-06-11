<?php

namespace lightningsdk\checkout\Model;

use Exception;
use Lightning\Model\BaseObject;
use Lightning\Model\User;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Mailer;
use Lightning\Tools\Messenger;
use Lightning\Tools\Request;
use Lightning\Tools\Security\Encryption;
use Lightning\Tools\Session\BrowserSession;
use Lightning\Tools\Session\DBSession;
use Lightning\Tools\ClientUser;

/**
 * Class Order
 * @package lightningsdk\checkout\Model
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
class OrderOverridable extends BaseObject {
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
     * @param null $order_id
     * @param bool $allowLocked
     *
     * @return Order
     *
     * @throws Exception
     */
    public static function loadBySession($order_id = null, $allowLocked = false) {
        // TODO: Check if there are multiple orders without payments, and merge them
        // (user_id and session_id)
        $criteria = self::loadBySessionCriteria($order_id, $allowLocked);
        $data = Database::getInstance()->selectRow(static::TABLE, $criteria);
        if ($data) {
            return new static($data);
        } else {
            return null;
        }
    }

    /**
     * Load a cart from an encrypted key. This is used when an email is sent out to
     * invite a user to complete a purchase. There are cases handled for whether a
     * user is signed in under a different account, or has created another cart.
     *
     * @param boolean $allowLocked
     *
     * @return Order|null
     *
     * @throws Exception
     */
    public static function loadOrMergeByEncryptedUrlKey($allowLocked = false) {
        if ($cart = Request::get('cart', Request::TYPE_ENCRYPTED)) {
            $data = json_decode(Encryption::aesDecrypt($cart, Configuration::get('user.key')), true);
            if (!empty($data['cart_id'])) {
                $cart = static::loadByID($data['cart_id']);
                // If the cart is locked and not allowed, return.
                if (!empty($cart->locked) && $allowLocked === false) {
                    return null;
                }
                // If the cart belongs to another user than who is signed in,
                // sign out the user an load the cart to a new session.
                $session = DBSession::getInstance();
                $user = ClientUser::getInstance();
                if (!$user->isAnonymous() && ClientUser::getInstance()->id != $cart->user_id) {
                    $session->destroy();
                    $session = DBSession::getInstance();
                }

                // We are only interested in unlocked carts in the current session.
                $existingCart = self::loadBySession(null, false);
                if (empty($existingCart)) {
                    // If there is no existing cart, then set this cart to
                    // the current session
                    $cart->session_id = $session->id;
                    $cart->save();
                } elseif ($existingCart->id != $cart->id) {
                    // If there is already an unlocked cart on this session
                    // delete the requested cart, and move all it's items
                    // to the existing session.
                    $existingCart->mergeFromAnotherCart($cart);
                    return $existingCart;
                }

                // If we made it here, the user already had the requested cart in
                // their session, and nothing needs to be done.
                return $cart;
            }
        }
    }

    public function getEncryptedUrlKey() {
        $data = [
            'cart_id' => $this->id,
        ];
        return Encryption::aesEncrypt(json_encode($data), Configuration::get('user.key'));
    }

    /**
     * Merges data from one cart into another. This should only be used
     * for unlocked carts in restoring user sessions.
     *
     * @param Order $cart
     *
     * @throws Exception
     */
    public function mergeFromAnotherCart($cart) {
        Database::getInstance()->update(LineItem::TABLE, [
            'order_id' => $this->id,
        ], [
            'order_id' => $cart->id,
        ]);
    }

    protected static function loadBySessionCriteria($order_id, $allowLocked) {
        $criteria = ['session_id' => DBSession::getInstance()->id];
        if (empty($allowLocked)) {
            $criteria['locked'] = 0;
        }
        if (!empty($order_id)) {
            $criteria['order_id'] = $order_id;
        }

        return $criteria;
    }

    /**
     * @return Order
     *
     * @throws Exception
     */
    public static function loadOrCreateBySession() {
        if ($order = self::loadBySession()) {
            return $order;
        } else {
            $data = [
                'user_id' =>
                    // If the user is signed in, we know their id.
                    ClientUser::getInstance()->id
                    // If the user is only being tracked, we know their id from the browser session.
                    ?? BrowserSession::getInstance()->user_id ?? 0,
                'session_id' => DBSession::getInstance()->id,
                'time' => time(),
                'referrer' => BrowserSession::getInstance()->referrer ?: 0,
            ];
            $order = new static($data);
            $order->save();
            return $order;
        }
    }

    public function getShippingAddress() {
        if (empty($this->shippingAddress)) {
            $this->shippingAddress = Address::loadByID($this->shipping_address);
        }
        return $this->shippingAddress;
    }

    /**
     * @return User
     *
     * @throws Exception
     */
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
        if ($discounts = $this->getDiscounts()) {
            $contents .= '<tr><td colspan="2"></td><td align="right">Discounts</td><td align="right">$' . number_format($discounts, 2) . '</td>';
        }
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

    public function getDiscounts($itemsOnly = false) {
        $this->discountValue = 0;
        // TODO: This can be optimized.
        $discounts = Discount::loadAll(['code' => ['IN', $this->__data['discounts']]]);
        foreach ($discounts as $d) {
            $this->discountValue += $d->getAmount($this, $itemsOnly);
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

    /**
     * @param int|object $product
     * @param int $qty
     * @param array $options
     *
     * @return int
     *
     * @throws Exception
     */
    public function addItem($product, $qty, $options = []) {
        // Make sure the order is saved, so it has an ID.
        if (empty($this->id)) {
            $this->save();
        }

        // Make sure the product is an object.
        if (is_int($product)) {
            $product = Product::loadByID($product);
        }

        if (empty($product)) {
            throw new Exception('Invalid product');
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

    /**
     * Check if there are any subscription items in this order.
     *
     * @return bool
     */
    public function hasSubscription() {
        foreach ($this->items as $item) {
            if ($item->getProduct()->isSubscription()) {
                return true;
            }
        }

        return false;
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

        // If there are any affiliates, pay them.
        $this->payAffiliates($payment);

        // If there is a user id and a mailing list ID, then subscribe the user to the list.
        if (!empty($this->user_id && $list = Configuration::get('modules.checkout.lists.any'))) {
            User::loadById($this->user_id)->subscribe($list);
        }

        return $payment;
    }

    public function recalculateAffiliates() {
        Database::getInstance()->delete('checkout_affiliate_payment', ['order_id' => $this->id]);
        $payments = Payment::loadAll(['order_id' => $this->id]);
        foreach ($payments as $payment) {
            $this->payAffiliates($payment);
        }
    }

    /**
     * @param Payment $payment
     *
     * @throws Exception
     */
    protected function payAffiliates($payment) {
        if ($this->referrer > 0) {
            $commissionScheme = Configuration::get('modules.checkout.affiliates.default_scheme');
            $commissionPercent = Configuration::get('modules.checkout.affiliates.default_percent');

            $level = 1;
            // The total amount of the order that can be used for commissions
            $orderBase = $this->getSubTotal() + $this->getDiscounts(true);
            // The adjusted total amount
            $commissionBase = $orderBase;
            switch ($commissionScheme) {
                case 'net':
                    // Commission based on total minus costs
                    /** @var LineItem $item */
                    foreach ($this->getItems() as $item) {
                        // Subtract the cost of each item included
                        $commissionBase -= $item->getAggregateOption('cost', 0) * $item->qty;
                    }
                    if ($commissionBase == $this->getTotal()) {
                        $commissionBase = 0;
                    }
                    $type = 'N';
                    break;

                case 'total':
                    // Commission based on total
                    $type = 'T';
                    break;
            }

            // Commission base is in decimal, but commission amount is an integer (decimal * 100)
            $commissionAmount = floor($commissionBase * $commissionPercent);

            $credit = new AffiliatePayment([
                'order_id' => $this->id,
                'payment_id' => $payment->id,
                'user_id' => $this->user_id,
                'affiliate_id' => $this->referrer,
                'amount' => $commissionAmount,
                'type' => $type . $level,
            ]);
            $credit->save();
        }
    }

    /**
     * @throws \Exception
     */
    public function sendNotifications() {
        // Set Meta Data for email.
        $mailer = new Mailer();
        $mailer->setCustomVariable('META', $this->meta);
        if ($address = $this->getShippingAddress()) {
            $mailer->setCustomVariable('SHIPPING_ADDRESS_BLOCK', $address->getHTMLFormatted());
        }

        $mailer->setCustomVariable('ORDER_DETAILS', $this->formatContents());

        // Send emails.
        /** @var LineItem $item */
        foreach ($this->getItems() as $item) {
            $options = $item->getProduct()->getAggregateOptions($item);
            if (!empty($options['customer_email'])) {
                $mailer->sendOne($options['customer_email'], $this->getUser());
                break;
            }
        }

        if ($buyer_email = Configuration::get('modules.checkout.buyer_email')) {
            $mailer->sendOne($buyer_email, $this->getUser());
        }
        if ($seller_email = Configuration::get('modules.checkout.seller_email')) {
            $mailer->sendOne($seller_email, Configuration::get('contact.to')[0]);
        }

        Messenger::message('Your order has been processed!');
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
