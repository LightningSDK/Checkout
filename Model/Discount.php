<?php

namespace Modules\Checkout\Model;

use Lightning\Model\Object;
use Lightning\Tools\Database;

class Discount extends Object {
    const TABLE = 'checkout_discount';
    const PRIMARY_KEY = 'discount_id';

    protected $__json_encoded_fields = ['discounts'];

    public static function loadByCode($code) {
        if ($discount = Database::getInstance()->selectRow(static::TABLE, ['code' => ['LIKE', $code]])) {
            return new static($discount);
        } else {
            return null;
        }
    }

    /**
     * @param Order $order
     *
     * @return float
     */
    public function getAmount($order, $itemsOnly = false) {
        // Has the minimum subtotal been met?
        if (!empty($this->discounts->minimum) && $order->getSubTotal() < $this->discounts->minimum) {
            // If not, there is no discount.
            return 0;
        }

        $discount = 0;

        // If this is a percentage discount, set it.
        if (!empty($this->discounts->percent)) {
            $discount = $this->discounts->percent * $order->getSubTotal() / 100;
        }

        // If this is a flat discount, set it.
        elseif (!empty($this->discounts->amount)) {
            $discount = $this->discounts->amount;
        }

        // If this is a shipping discount, add it.
        elseif (!$itemsOnly && !empty($this->discounts->shippingPercent)) {
            $discount = $this->discounts->shippingPercent * $order->getShipping() / 100;
        }

        // If this is a shipping discount, add it.
        elseif (!$itemsOnly && !empty($this->discounts->shippingPercent)) {
            $discount = min($this->discounts->shippingAmount, $order->getShipping());
        }

        // Make sure the discount does not exceed the maximum value.
        if (!empty($this->discounts->maximum)) {
            $discount = min($discount, $this->discounts->maximum);
        }

        return number_format(-$discount, 2);
    }
}
