<?php

namespace lightningsdk\checkout\Handlers;

use lightningsdk\checkout\Model\Order;

abstract class Payment {
    abstract public function getPage(Order $cart);
}
