<?php

namespace Modules\Checkout\Pages;

use Exception;
use Lightning\Tools\Configuration;
use Lightning\Tools\Navigation;
use Lightning\Tools\Request;
use Lightning\Tools\Template;
use Lightning\View\JS;
use Lightning\View\Page;
use Modules\Checkout\Model\Address;
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

    protected $rightColumn = false;
    protected $share = false;

    public function hasAccess() {
        return true;
    }

    public function get() {
        \Modules\Checkout\View\Checkout::init();
        $nextPage = $this->nextRequiredPage();
        $this->page[0] = 'checkout/' . $nextPage;

        $template = Template::getInstance();
        JS::set('modules.checkout.hideCartModal', true);

        switch ($nextPage) {
            case self::PAGE_PAYMENT_OPTIONS:
                $handlers = \Modules\Checkout\View\Checkout::getHandlers();
                $template->set('handlers', $handlers);
                break;
            case self::PAGE_PAYMENT:
                JS::set('modules.checkout.total', $this->order->getTotal());
                // TODO: This should be configurable.
                JS::set('modules.checkout.currency', 'USD');
                $handlerId = Request::get('gateway');
                $handler = \Modules\Checkout\View\Checkout::getHandler($handlerId);
                $handler->init();

                $this->page = $handler->getPage($this->order);
                break;
        }

        $template->set('cart', $this->order);
    }

    public function postShipping() {
        // TODO: post shipping here
        $name = Request::post('name');
        $street = Request::post('street');
        $street2 = Request::post('street2');
        $city = Request::post('city');
        $state = Request::post('state');
        $zip = Request::post('zip');
        $country = Request::post('country');

        $address = new Address([
            'name' => $name,
            'street' => $street,
            'street2' => $street2,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'country' => $country,
        ]);

        $address->save();
        $order = Order::loadBySession();

        $order->shipping_address = $address->id;
        $order->save();

        Navigation::redirect('/store/checkout?page=' . self::PAGE_PAYMENT);
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    protected function nextRequiredPage() {
        $requestedPage = Request::get('page');

        // If this is an order confirmation page:
        if ($requestedPage === self::PAGE_CONFIRMATION) {
            $id = Request::get('id', Request::TYPE_INT);
            $order = Order::loadBySession($id, true);
            if (!empty($order->id) && $order->locked === "1") {
                $this->order = $order;
                return self::PAGE_CONFIRMATION;
            }
            throw new Exception('Invalid order ID');
        }

        $this->order = Order::loadBySession();
        if (empty($this->order)) {
            throw new Exception('Your cart is empty');
        }

        // If the order is empty, show the empty order page.
        if (count($this->order->getItems()) == 0) {
            return self::PAGE_EMPTY;
        }

        // Shipping page
        if ((
            $requestedPage === self::PAGE_CHECKOUT
            || $requestedPage === self::PAGE_SHIPPING
        ) && ($this->order->requiresShippingAddress())) {
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

        // Cart verification page
        return self::PAGE_CART;
    }
}
