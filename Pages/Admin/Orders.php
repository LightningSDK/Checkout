<?php

namespace lightningsdk\checkout\Pages\Admin;

use Exception;
use lightningsdk\core\Pages\Table;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Navigation;
use lightningsdk\core\Tools\Output;
use lightningsdk\core\Tools\Request;
use lightningsdk\core\Tools\Template;
use lightningsdk\core\View\JS;
use lightningsdk\core\View\TablePresets;
use lightningsdk\checkout\Model\Order;
use lightningsdk\core\Tools\ClientUser;

class Orders extends Table {
    const TABLE = 'checkout_order';
    const PRIMARY_KEY = 'order_id';

    protected $field_order = ['order_id', 'status', 'email', 'shipping_address'];
    protected $sort = ['order_id' => 'DESC'];
    protected $addable = false;
    protected $exportable = true;

    protected $preset = [
        'details' => [
            'type' => 'json',
            'unlisted' => true,
        ],
        'session_id' => 'hidden',
        'status' => 'hidden',
        'time' => [
            'type' => 'datetime',
            'editable' => false,
            'allow_blank' => true,
        ],
        'paid' => [
            'type' => 'datetime',
            'editable' => false,
            'allow_blank' => true,
        ],
        'shipped' => [
            'type' => 'datetime',
            'editable' => false,
            'allow_blank' => true,
        ],
        'email' => [
            'editable' => false,
        ],
        'shipping_address' => [
            'editable' => false,
            'exportable' => false,
        ],
        'tax' => [
            'editable' => false,
        ],
        'shipping' => [
            'editable' => false,
        ],
        'total' => [
            'editable' => false,
        ],
        'locked' => [
            'type' => 'checkbox',
        ],
        'contents' => [
            'editable' => false,
            'unlisted' => true,
        ],
        'discounts' => 'json',
        'referrer' => [],
    ];

    protected $accessControl = ['locked' => 1];

    protected $joins = [
        [
            'left_join' => 'checkout_address',
            'on' => ['address_id' => ['expression' => 'shipping_address']],
        ],
        [
            'left_join' => 'user',
            'using' => ['user_id'],
        ]
    ];
    protected $joinFields = ['checkout_address.*', 'email'];

    public function hasAccess() {
        return ClientUser::requireAdmin();
    }

    protected function initSettings() {
        parent::initSettings();
        $this->preset['shipping_address']['render_list_field'] = function(&$row) {
                return $row['name'] . '<br>' . $row['street'] . ' ' . $row['street2'] . '<br>' . $row['city'] . ', ' . $row['state'] . ' ' . $row['zip'];
            };
        $this->preset['email']['render_list_field'] = function(&$row) {
                return $row['email'];
            };
        $this->preset['contents']['display_value'] = function(&$row) {
            $order = Order::loadByID($row['order_id']);
            return $order->formatContents([
                'links' => true,
            ]);
        };
        $this->preset['referrer'] = TablePresets::userSearch('referrer');

        $this->action_fields = [
            'Cancel' => [
                'type' => 'action',
                'action' => 'cancel',
                'display_name' => 'Cancel',
                'display_value' => '<img src="/images/checkout/cancel.png" border="0">',
                'condition' => function(&$row) {
                    return empty($row['shipped']);
                }
            ],
            'Ship' => [
                'type' => 'action',
                'action' => 'ship',
                'display_name' => 'Ship',
                'display_value' => '<img src="/images/checkout/ship.png" border="0">',
                'condition' => function(&$row) {
                    return empty($row['shipped']);
                }
            ],
            'Mark Fulfilled' => [
                'type' => 'action',
                'action' => 'mark_shipped',
                'display_name' => 'Mark Fulfilled',
                'display_value' => '<img src="/images/checkout/shipped.png" border="0">',
                'condition' => function(&$row) {
                    return empty($row['shipped']);
                }
            ]
        ];
    }

    protected function afterPost() {
        $order = Order::loadByID($this->id);
        $order->recalculateAffiliates();
    }

    public function getView() {
        $this->addFulfillmentHandlers();
        return parent::getView();
    }

    public function getEdit() {
        $this->addFulfillmentHandlers();
        return parent::getEdit();
    }

    protected function addFulfillmentHandlers() {
        $this->getRow();
        $order = new Order($this->list);
        $required_handlers = $order->getRequiredFulfillmentHandlers();

        foreach (Configuration::get('modules.checkout.fulfillment_handlers') as $reference => $connector) {
            if (in_array($reference, $required_handlers)) {
                if (($url = $connector::FULFILLMENT_URL) && ($button_text = $connector::FULFILLMENT_TEXT)) {
                    $this->custom_buttons[] = [
                        'type' => self::CB_ACTION_LINK,
                        'url' => $url,
                        'text' => $button_text,
                    ];
                }
            }
        }
    }

    public function get() {
        if (Request::get('label-popup')) {
            JS::startup('window.open(unescape(lightning.query("label-popup")))');
        }
        parent::get();
    }

    /**
     * Load the shipping fulfilment page.
     *
     * @throws Exception
     */
    public function getShip() {
        $order = Order::loadByID($this->id);
        $handlers = $order->getRequiredFulfillmentHandlers();
        if (empty($handlers)) {
            throw new Exception('No fulfillment handlers for the unfulfilled items.');
        }

        if (empty($handlers)) {
            Output::error('There are no fulfillment handlers available for this order.');
        }

        if ($specific_handler = Request::get('handler')) {
            $handler = Configuration::get('modules.checkout.fulfillment_handlers.' . $specific_handler);
        } elseif (count($handlers) == 1) {
            $handler = Configuration::get('modules.checkout.fulfillment_handlers.' . $handlers[0]);
        } elseif (count($handlers) > 1) {
            $template = Template::getInstance();
            $template->set('handlers', $handlers);
            $template->set('order_id', $order->id);
            $this->page = ['fulfillment_selector', 'Checkout'];
            return;
        }

        // Redirect to the fulfillment page.
        if (!empty($handler)) {
            Navigation::redirect($handler::FULFILLMENT_URL, ['id' => $this->id, 'return' => 'fulfill']);
        } else {
            Output::error('Fulfillment handler not found.');
        }
    }

    public function getMarkShipped() {
        $order = Order::loadByID($this->id);

        if (empty($order)) {
            Output::error('Invalid order ID');
        }

        $template = Template::getInstance();
        $template->set('order_id', $order->id);
        $this->page = ['confirm_mark_shipped', 'Checkout'];
    }

    public function postMarkShipped() {
        $order = Order::loadByID($this->id);
        $order->markFulfilled(true);
        $order->save();
        Navigation::redirect();
    }

    public function getCancel() {
        $order = Order::loadByID($this->id);
    }

    public function getCancelConfirm() {
        $order = Order::loadByID($this->id);

        // Cancel order
        $order->status = Order::STATUS_CANCELED;

        if (Request::get('refund', Request::TYPE_BOOLEAN)) {
            $order->refund();
        }
    }
}
