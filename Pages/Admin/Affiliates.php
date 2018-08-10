<?php

namespace Modules\Checkout\Pages\Admin;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Database;
use Lightning\Tools\Template;
use Lightning\View\Page;

class Affiliates extends Page {

    protected $page = ['admin/affiliates', 'Checkout'];

    public function hasAccess() {
        return ClientUser::requireAdmin();
    }

    public function get() {
        $affilates = Database::getInstance()->selectAllQuery([
            'select' => '*',
            'from' => [
                'select' => [
                    '*',
                    'total' => ['expression' => 'SUM(amount)']
                ],
                'as' => 'totals',
                'from' => 'checkout_affiliate_payment',
                'group_by' => 'affiliate_id',
            ],
            'join' => [
                'left_join' => 'user',
                'on' => ['totals.affiliate_id' => ['expression' => 'user.user_id']],
            ],
        ]);

        $template = Template::getInstance();
        $template->set('affiliates', $affilates);
    }
}
