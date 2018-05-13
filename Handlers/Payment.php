<?php

namespace Modules\Checkout\Handlers;

use Modules\Checkout\Model\Order;

abstract class Payment {
    abstract public function getPage(Order $cart);
}
