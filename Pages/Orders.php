<?php

namespace Modules\Checkout\Pages;

use Lightning\Pages\Table;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Navigation;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Template;
use Lightning\View\JS;
use Modules\Checkout\Model\Order;
use Lightning\Tools\ClientUser;

class Orders extends Table {
    const TABLE = 'checkout_order';
    const PRIMARY_KEY = 'order_id';

    protected $field_order = ['order_id', 'status', 'email', 'shipping_address'];
    protected $sort = ['order_id' => 'DESC'];

    protected $preset = [
        'details' => [
            'type' => 'json',
            'unlisted' => true,
        ],
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
        'contents' => [
            'editable' => false,
            'unlisted' => true,
        ],
        'discounts' => 'json',
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
        $this->preset['shipping_address'] = [
            'render_list_field' => function(&$row) {
                return $row['name'] . '<br>' . $row['street'] . ' ' . $row['street2'] . '<br>' . $row['city'] . ', ' . $row['state'] . ' ' . $row['zip'];
            }
        ];
        $this->preset['email'] = [
            'render_list_field' => function(&$row) {
                return $row['email'];
            }
        ];
        $this->preset['contents']['display_value'] = function(&$row) {
            $order = Order::loadByID($row['order_id']);
            return $order->formatContents();
        };

        $this->action_fields = [
            'Ship' => [
                'type' => 'action',
                'action' => 'ship',
                'display_name' => 'Ship',
                'display_value' => '<img src="/images/checkout/ship.png" border="0">',
                'condition' => function(&$row) {
                    return empty($row['shipped']);
                }
            ]
        ];
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
                if (($url = $connector::FULFILLMENT_URL) && ($button_text = $connector::FULLFILLMENT_TEXT)) {
                    $this->custom_buttons[] = [
                        'type' => self::CB_ACTION_LINK,
                        'url' => $url,
                        'text' => $button_text,
                    ];
                }
            }
        }
    }

    /**
     * The shipping page handler.
     */
    public function getShip() {
        if ($shipping_module = Configuration::get('modules.checkout.shipping_module')) {
            $this->getRow();
            $this->page = ['ship-confirmation-print', 'Checkout'];
            $this->fullWidth = false;
            $template = Template::getInstance();
            $template->set('order', $this->list);
            $db = Database::getInstance();
            $template->set('shipping_address', $db->selectRow('checkout_address', ['address_id' => $this->list['shipping_address']]));
            $template->set('user', $db->selectRow('user', ['user_id' => $this->list['user_id']]));
        }
        if ($this->list['shipped'] > 0) {
            Output::error('This item has already been shipped');
        }
    }

    public function postShip() {
        $db = Database::getInstance();
        $this->getRow();
        $redirect_params = [];

        if (
            Request::post('print-label', 'boolean') &&
            $shipping_module = Configuration::get('modules.checkout.shipping_module')
        ) {
            // Create a shipping label.
            $shipping_data_model = '\Modules\\' . $shipping_module . '\\Model\\ShipmentData';
            $shipping_model = '\Modules\\' . $shipping_module . '\\Model\\Shipment';

            // Load shipping data
            $shipping_address = $db->selectRow('checkout_address', ['address_id' => $this->list['shipping_address']]);
            $user = $db->selectRow('user', ['user_id' => $this->list['user_id']]);
            $from_address = Configuration::get('modules.checkout.from_address');

            // Create the shipment.
            $shipment_data = new $shipping_data_model();
            $shipment_data->setFromAddress($from_address['name'], $from_address['company'], $from_address['street'], $from_address['street2'], $from_address['city'], $from_address['state'], $from_address['zip'], $from_address['country'], $from_address['phone'], $from_address['email']);
            $shipment_data->setToAddress($shipping_address['name'], '', $shipping_address['street'], $shipping_address['street2'], $shipping_address['city'], $shipping_address['state'], $shipping_address['zip'], $shipping_address['country'], '', $user['email']);

            // Set the package size.
            $height = Request::get('package-height', 'int');
            $width = Request::get('package-width', 'int');
            $length = Request::get('package-length', 'int');
            $shipment_data->setParcelInches($length, $width, $height);
            $weight = Request::get('package-weight', 'int');
            switch (Request::get('package-weight-units')) {
                case 'oz':
                    $shipment_data->setParcelOz($weight);
                    break;
                case 'lb':
                    $shipment_data->setParcelLbs($weight);
                    break;
            }

            // Create the label.
            $shipment = new $shipping_model($shipment_data);
            $shipment->create();
            $shipment->charge();

            // Get the popup window and redirect.
            $redirect_params['label-popup'] = $shipment->getLabelURL();
        }

        if ( Request::post('notify', 'boolean')) {
            // Send an email to the user. Include the tracking number if available.
        }

        // Mark the order as shipped.
        $db->update('checkout_order', ['shipped' => time()], ['order_id' => $this->list['order_id']]);

        // Redirect.
        Navigation::redirect('/admin/orders', $redirect_params);
    }

    public function get() {
        if (Request::get('label-popup')) {
            JS::startup('window.open(unescape(lightning.query("label-popup")))');
        }
        parent::get();
    }
}
