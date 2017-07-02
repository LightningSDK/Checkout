<?php

namespace Modules\Checkout\Model;

use Exception;
use Lightning\Model\Object;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Template;

class Product extends Object {
    const TABLE = 'checkout_product';
    const PRIMARY_KEY = 'product_id';

    protected $__json_encoded_fields = ['options' => ['type' => 'array']];

    public static function loadByURL($url) {
        $data = Database::getInstance()->selectRow(self::TABLE, ['url' => ['LIKE', $url]]);
        if (!empty($data)) {
            return new static($data);
        } else {
            return null;
        }
    }

    public function optionsSatisfied($options) {
        if (!empty($this->options['options'])) {
            foreach ($this->options['options'] as $option => $settings) {
                if (!empty($settings['required']) && empty($options[$option])) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getPopupOptionsForm() {
        $template = new Template();
        if (!empty($this->options['options_popup_template'])) {
            $template->set('fields_template', $this->options['options_popup_template']);
        } else {
            $template->set('fields_template', '');
        }
        $template->set('product', $this);
        return $template->build(['product_popup', 'Checkout'], true);
    }

    public function getImage($type = 'listing-image') {
        $image = null;
        try {
            $options = $this->options;
            array_walk_recursive($options, function($val, $key) use (&$image, $type) {
                switch ($key) {
                    case 'og-image':
                    case 'image':
                        $image = $val;
                        if ($key == $type) {
                            throw new Exception('Complete');
                        }
                        break;
                    case 'listing-image':
                        $image = $val;
                        if ($key == $type) {
                            throw new Exception('Complete');
                        }
                }
            });
        } catch (Exception $e) {};
        return $image;
    }

    public function aggregateOptions($selected_options) {
        // TODO: This does not take into account options on the same level.
        $options = $this->options;
        while (!empty($options['options'])) {
            // Iterate over the options
            $child_options = $options['options'];
            unset($options['options']);
            foreach ($child_options as $option_name => $settings) {
                // If the option is set, the child options will override the parent options.
                $selected_value = $selected_options[$option_name];
                if (!empty($settings['values'][$selected_value])) {
                    $options = $settings['values'][$selected_value] + $options;
                }
            }
        }
        return $options;
    }

    /**
     * Search for options mapped as specific fields. This currently only supports qty.
     * If an option has a setting 'map' => 'qty', then whatever value is entered for this option
     * may override the qty field. This is handled in the Cart controller.
     *
     * @param string $mapped_as
     *   The value of the map.
     *
     * @return string
     *   The name of the option.
     */
    public function getMappedOption($mapped_as) {
        $product_options = $this->options;

        foreach (new \RecursiveIteratorIterator(new \RecursiveArrayIterator($product_options), \RecursiveIteratorIterator::SELF_FIRST) as $key => $item) {
            if (!empty($item['map']) && $item['map'] == $mapped_as) {
                return $key;
            }
        }
    }

    /**
     * Get the aggregated options for a line item with this product.
     *
     * @param LineItem $item
     *   The existing line item with options saved.
     *
     * @return array
     *   A key/value array of set options.
     */
    public function getAggregateOptions(LineItem $item) {
        return $this->aggregateOptions($item->options);
    }

    public function getURL() {
        return '/store/' . $this->url;
    }

    public function isAvailable() {
        if ($this->active == 0) {
            return false;
        }
        if ($this->qty == 0) {
            return false;
        }

        return true;
    }

    public function renderCheckoutOptions() {
        $template = Template::getInstance();
        $template->set('product', $this);
        if ($this->isAvailable()) {
            return $template->build(['options', 'Checkout'], true);
        } else {
            return $template->build(['unavailable', 'Checkout'], true);
        }
    }

    public function printTotalAmount() {
        if (!empty($this->options['subscription'])) {
            if (is_array($this->options['subscription'])) {
                // TODO: This information can be developed from this array
            }
            elseif ($handler = Configuration::get('modules.checkout.handler')) {
                $connector = new $handler();
                return $handler::printSubscription($this->options['subscription']);
            }
        }
    }
}
