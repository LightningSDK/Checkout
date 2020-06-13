<?php

namespace lightningsdk\checkout\Pages\Admin;

use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\Tools\Database;
use lightningsdk\core\Tools\Template;
use lightningsdk\core\View\Page;

class Affiliates extends Page {

    protected $page = ['admin/affiliates', 'lightningsdk/checkout'];

    public function hasAccess() {
        return ClientUser::requireAdmin();
    }

    public function get() {
        $query = $this->getAffiliatesDueQuery();
        $affilates = Database::getInstance()->selectAllQuery($query);

        $template = Template::getInstance();
        $template->set('affiliates', $affilates);
    }

    protected function getAffiliatesDueQuery() {
        return [
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
        ];
    }
}
