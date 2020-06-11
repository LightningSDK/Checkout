<?php

namespace lightningsdk\checkout\Jobs;

use lightningsdk\core\Jobs\Job;
use lightningsdk\core\Model\Message;
use lightningsdk\core\Model\Subscription;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Mailer;
use lightningsdk\checkout\Model\Order;

class Mail extends Job {

    const NAME = 'Checkout Mailer';

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var Message
     */
    protected $message;

    /**
     * @param $job
     *
     * @throws \Exception
     */
    public function execute($job) {

        $this->out('Sending emails to complete checkout');

        // Load the carts
        $abandonedCarts = $this->getCarts();
        if (empty($abandonedCarts)) {
            return;
        }

        // Load the message
        $this->message = $this->getMessage();

        // Configure the mailer
        $this->mailer = new Mailer();
        $this->mailer->setMessage($this->message);
        $this->checkoutRoot = Configuration::get('web_root') . '/store/checkout?cart=';


        // Determine whether the users should be notified.
        $max_messages = Configuration::get('modules.checkout.abandoned.max_messages', 5);
        // Default 1 day
        $min_wait = Configuration::get('modules.checkout.abandoned.min_wait', 1 * 24 * 60 * 60);
        // Default 2 hours
        $initial_wait = Configuration::get('modules.checkout.abandoned.initial_wait', 2 * 60 * 60);
        foreach ($abandonedCarts as $cart) {
            /*** @var Order $cart */

            // If there hasn't been enough time since they were on the site, skip this order.
            if ($cart->time > time() - $initial_wait) {
                continue;
            }

            // If the user is not subscribed to anything, then bypass this.
            if (empty(Subscription::getUserLists($cart->user_id))) {
                continue;
            }

            // If the user has not received a message in x days AND has
            // not received x message total, then send an email.
            $stats = $this->message->getStats($cart->user_id);
            if (
                $stats['count'] < $max_messages
                && $stats['last'] < time() - $min_wait
            ) {
                $this->out('Sending resume cart email to ' . $cart->getUser()->email);
                $this->sendMessage($cart);
            }
        }
    }

    protected function getCarts() {
        // Load the abandoned carts
        return Order::loadAll([
            'user_id' => ['>', 0],
            'locked' => 0,
            'time' => ['>', time() - (30 * 24 * 60 * 60)],
        ]);
    }

    protected function getMessage() {
        if ($message_id = Configuration::get('modules.checkout.abandoned.email')) {
            return Message::loadByID($message_id);
        } else {
            throw new \Exception('No abandoned cart email is set.');
        }
    }

    /**
     * @param Order $cart
     */
    protected function sendMessage($cart) {
        $this->mailer->clearAddresses();
        $this->mailer->setUser($cart->getUser());
        $this->mailer->setCustomVariable('CART_CONTENTS', $cart->formatContents());
        $this->mailer->setCustomVariable('CHECKOUT_URL', $this->checkoutRoot . $cart->getEncryptedUrlKey());
        $this->mailer->sendMessage();
    }
}
