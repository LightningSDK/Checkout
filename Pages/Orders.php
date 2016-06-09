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
use Overridable\Lightning\Tools\ClientUser;

class Orders extends Table {
    const TABLE = 'checkout_order';
    const PRIMARY_KEY = 'order_id';

    protected $field_order = ['order_id', 'status', 'shipping_address'];
    protected $sort = ['order_id' => 'DESC'];

    protected $preset = [
        'time' => 'datetime',
    ];

    protected $joins = [
        [
            'left_join' => 'checkout_address',
            'on' => ['address_id' => ['expression' => 'shipping_address']],
        ]
    ];
    protected $joinFields = ['checkout_address.*'];

    public function hasAccess() {
        return ClientUser::requireAdmin();
    }

    public function initSettings() {
        parent::initSettings();
        $this->preset['shipping_address'] = [
            'render_list_field' => function(&$row) {
                return $row['name'] . '<br>' . $row['street'] . ' ' . $row['street2'] . '<br>' . $row['city'] . ', ' . $row['state'] . ' ' . $row['zip'];
            }
        ];

        $this->action_fields = [
            'Ship' => [
                'type' => 'action',
                'action' => 'ship',
                'display_value' => '<img src="/images/checkout/ship.png" border="0">',
                'condition' => function(&$row) {
                    return empty($row['shipped']);
                }
            ]
        ];
    }

    public function getShip() {
        if ($shipping_module = Configuration::get('checkout.shipping_module')) {
            $this->getRow();
            $this->page = ['ship-confirmation-print', 'Checkout'];
            $this->fullWidth = false;
            $template = Template::getInstance();
            $template->set('order', $this->list);
            $db = Database::getInstance();
            $template->set('shipping_address', $db->selectRow('checkout_address', ['address_id' => $this->list['shipping_address']]));
            $template->set('user', $db->selectRow('user', ['user_id' => $this->list['user_id']]));
        }
        if ($this->list['shipped'] == 1) {
            Output::error('This item has already been shipped');
        }
    }

    public function postShip() {
        $db = Database::getInstance();
        $this->getRow();
        $redirect_params = [];

        if (
            Request::post('print-label', 'boolean') &&
            $shipping_module = Configuration::get('checkout.shipping_module')
        ) {
            // Create a shipping label.
            $shipping_data_model = '\Modules\\' . $shipping_module . '\\Model\\ShipmentData';
            $shipping_model = '\Modules\\' . $shipping_module . '\\Model\\Shipment';

            // Load shipping data
            $shipping_address = $db->selectRow('checkout_address', ['address_id' => $this->list['shipping_address']]);
            $user = $db->selectRow('user', ['user_id' => $this->list['user_id']]);
            $from_address = Configuration::get('checkout.from_address');

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
        $db->update('checkout_order', ['shipped' => 1], ['order_id' => $this->list['order_id']]);

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
