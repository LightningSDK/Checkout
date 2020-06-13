<?php

namespace lightningsdk\checkout\Pages;

use Exception;
use lightningsdk\core\Model\User;
use lightningsdk\core\Tools\Navigation;
use lightningsdk\core\Tools\Request;
use lightningsdk\core\Tools\Template;
use lightningsdk\core\View\Field\Location;
use lightningsdk\core\View\JS;
use lightningsdk\core\View\Page;
use lightningsdk\checkout\Model\Address;
use lightningsdk\checkout\Model\Order;

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
     * @var object
     */
    protected $paymentHandler;

    /**
     * @var array
     */
    protected $paymentHandlers;

    protected $page = ['checkout/empty', 'lightningsdk/checkout'];

    protected $rightColumn = false;
    protected $share = false;

    public function hasAccess() {
        return true;
    }

    public function get() {
        // Attempt to load a cart from a previous session.
        Order::loadOrMergeByEncryptedUrlKey();

        // Initialize the module.
        \lightningsdk\checkout\View\Checkout::init();

        // Initialize the page display.
        $nextPage = $this->nextRequiredPage();
        $this->page[0] = 'checkout/' . $nextPage;

        $template = Template::getInstance();
        JS::set('modules.checkout.hideCartModal', true);

        switch ($nextPage) {
            case self::PAGE_SHIPPING:
                JS::startup('lightning.tracker.track(lightning.tracker.events.initiateCheckout, {});');
                JS::startup('lightning.modules.checkout.initCountrySelection()', ['lightningsdk/checkout' => 'Checkout.js']);
                JS::set('modules.checkout.states', Location::getStateOptions(null));
                break;
            case self::PAGE_PAYMENT_OPTIONS:
                $template->set('handlers', $this->paymentHandlers);
                break;
            case self::PAGE_PAYMENT:
                JS::set('modules.checkout.total', $this->order->getTotal());
                // TODO: This should be configurable.
                JS::set('modules.checkout.currency', 'USD');
                $this->paymentHandler->init();

                $this->page = $this->paymentHandler->getPage($this->order);
                break;
        }

        $template->set('cart', $this->order);
    }

    public function postShipping() {
        $name = Request::post('name');
        $street = Request::post('street');
        $street2 = Request::post('street2');
        $city = Request::post('city');
        $state = Request::post('state');
        $zip = Request::post('zip');
        $country = Request::post('country');
        $email = Request::post('email', Request::TYPE_EMAIL);

        $user = User::addUser($email, ['full_name' => $name]);

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
        if (empty($order->user_id)) {
            $order->user_id = $user->id;
        }

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
            $this->paymentHandlers = \lightningsdk\checkout\View\Checkout::getHandlers();

            if (!empty($this->paymentHandlers) && !empty($this->paymentHandlers[$paymentHandler])) {
                $this->paymentHandler = $this->paymentHandlers[$paymentHandler];
                return self::PAGE_PAYMENT;
            } elseif (count($this->paymentHandlers) == 1) {
                $this->paymentHandler = current($this->paymentHandlers);
                return self::PAGE_PAYMENT;
            } elseif (count($this->paymentHandlers) > 1) {
                return self::PAGE_PAYMENT_OPTIONS;
            } else {
                throw new Exception('No payment handlers configured');
            }
        }

        // Cart verification page
        return self::PAGE_CART;
    }
}
