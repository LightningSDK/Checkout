<?php

namespace lightningsdk\checkout\Pages;

use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\Tools\Database;
use lightningsdk\core\Tools\Navigation;
use lightningsdk\core\Tools\Template;
use lightningsdk\core\View\Page;

class AffiliateSales extends Page {

    protected $page = ['affiliate_sales', 'lightningsdk/checkout'];

    public function hasAccess() {
        // TODO: Make sure the user is an affiliate first
        $user = ClientUser::getInstance();
        if ($user->isAnonymous()) {
            Navigation::redirect('/');
        } else {
            return true;
        }
    }

    public function get() {
        $template = Template::getInstance();
        $user = ClientUser::getInstance();
        $orders = Database::getInstance()->selectAllQuery($this->getOrdersQuery($user));

        $template->set('orders', $orders);

        $balance = Database::getInstance()->selectFieldQuery($this->getBalanceQuery($user), 'balance');

        $template->set('balance', $balance);
    }

    protected function getOrdersQuery($user) {
        return [
            'select' => [
                'order_id' => 'checkout_affiliate_payment.order_id',
                'time' => 'checkout_payment.time',
                'name' => ['expression' => 'CONCAT(user.first, " ", user.last)'],
                'commission' => 'checkout_affiliate_payment.amount'
            ],
            'from' => 'checkout_affiliate_payment',
            'join' => [
                [
                    'left_join' => 'user',
                    'on' => ['user.user_id' => ['checkout_affiliate_payment.user_id']],
                ], [
                    'left_join' => 'checkout_payment',
                    'on' => ['checkout_payment.order_id' => ['checkout_affiliate_payment.order_id']],
                ]
            ],
            'order_by' => ['time' => 'DESC'],
            'where' => [
                'affiliate_id' => $user->id,
            ]
        ];
    }

    protected function getBalanceQuery($user) {
        return [
            'select' => [
                'balance' => ['expression' => 'SUM(amount)']
            ],
            'from' => 'checkout_affiliate_payment',
            'where' => [
                'affiliate_id' => $user->id,
            ]
        ];
    }
}
