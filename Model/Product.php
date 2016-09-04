<?php

namespace Modules\Checkout\Model;

use Exception;
use Lightning\Model\Object;
use Lightning\Tools\Database;
use Lightning\Tools\Template;

class Product extends Object {
    const TABLE = 'checkout_product';
    const PRIMARY_KEY = 'product_id';

    protected $__json_encoded_fields = ['options'];

    public static function loadByURL($url) {
        $data = Database::getInstance()->selectRow(self::TABLE, ['url' => ['LIKE', $url]]);
        if (!empty($data)) {
            return new static($data);
        } else {
            return null;
        }
    }

    public function optionsSatisfied($options) {
        if (!empty($this->option->optionss)) {
            foreach ($this->options->options as $option => $settings) {
                if (!empty($settings->required) && empty($options[$option])) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getPopupOptionsForm() {
        $template = new Template();
        if (!empty($this->options->options_popup_template)) {
            $template->set('fields_template', $this->options->options_popup_template);
        } else {
            $template->set('fields_template', ['default_options_layout', 'Checkout']);
        }
        $template->set('product', $this);
        return $template->build(['options', 'Checkout'], true);
    }

    public function getImage() {
        $image = null;
        try {
            $options = json_decode($this->__json_encoded_source['options'], true);
            array_walk_recursive($options, function($val, $key) use (&$image) {
                switch ($key) {
                    case 'og-image':
                    case 'image':
                        $image = $val;
                        break;
                    case 'listing-image':
                        $image = $val;
                        throw new Exception('Complete');
                }
            });
        } catch (Exception $e) {};
        return $image;
    }
}
