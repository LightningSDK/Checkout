<?php

namespace Source\Modules\Checkout\Pages;

use Lightning\Tools\Configuration;
use Lightning\Tools\Navigation;
use Lightning\Tools\Request;
use Lightning\Tools\Session\DBSession;
use Lightning\View\Page;
use Modules\Checkout\Model\Order;

class Checkout extends Page {

    const PAGE_EMPTY = 'empty';
    const PAGE_CART = 'cart';

    // This is a placeholder that is intended to skip the cart page
    // and go directly to whatever is next.
    const PAGE_CHECKOUT = 'checkout';
    const PAGE_SHIPPING = 'shipping';
    const PAGE_PAYMENT_OPTIONS = 'payment_options';
    const PAGE_PAYMENT = 'payment';
    const PAGE_CONFIRMATION = 'confirmation';

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var string|array
     */
    protected $paymentHandler;

    protected $page = ['checkout/empty', 'Checkout'];

    public function get() {
        $nextPage = $this->nextRequiredPage();
        $this->page[0] = 'checkout/' . $nextPage;
        switch($nextPage) {
            case self::PAGE_EMPTY:
                break;
            case self::PAGE_CART:
                break;
            case self::PAGE_SHIPPING:
                break;
            case self::PAGE_PAYMENT_OPTIONS:
                break;
            case self::PAGE_PAYMENT:
                break;
            case self::PAGE_CONFIRMATION:
                break;
        }
    }

    public function postShipping() {
        // TODO: post shipping here

        Navigation::redirect('/checkout?page=' . self::PAGE_PAYMENT);
    }

    protected function nextRequiredPage() {
        $requestedPage = Request::get('page') ?: $this->nextRequiredPage();
        $this->order = Order::loadBySession();

        // If the order is empty, show the empty order page.
        if (count($this->order->getItems()) == 0) {
            return self::PAGE_EMPTY;
        }

        // Shipping page
        if (($requestedPage === self::PAGE_CHECKOUT || $requestedPage === self::PAGE_SHIPPING) && ($this->order->requiresShippingAddress())) {
            return self::PAGE_SHIPPING;
        }

        // Payment options page
        if ($requestedPage === self::PAGE_PAYMENT) {
            $paymentHandler = Request::get('gateway');
            $paymentHandlers = Configuration::get('modules.checkout.handlers');

            if (!empty($paymentHandler) && !empty($paymentHandlers[$paymentHandler])) {
                $this->paymentHandler = $paymentHandlers[$paymentHandler];
                return self::PAGE_PAYMENT;
            }
            if (count($paymentHandlers) > 1) {
                return self::PAGE_PAYMENT_OPTIONS;
            } else {
                $this->paymentHandler = current($paymentHandlers);
                return self::PAGE_PAYMENT;
            }
        }

        if ($requestedPage === self::PAGE_CONFIRMATION) {
            $id = Request::get('id', Request::TYPE_INT);
            $order = Order::loadBySession($id);
            if (!empty($order->id)) {
                $this->order = $order;
                return self::PAGE_CONFIRMATION;
            }
        }

        // Cart verification page
        return self::PAGE_CART;
    }
}
