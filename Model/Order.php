<?php

namespace Modules\Checkout\Model;

use Lightning\Model\Object;
use Lightning\Tools\Database;

class Order extends Object {
    const TABLE = 'checkout_order';
    const PRIMARY_KEY = 'order_id';

    protected $__json_encoded_fields = ['details'];

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
        if ($paid >= $this->total) {
            $this->paid = 1;
            $this->save();
        }

        return $payment;
    }
}
