<?php

namespace Modules\Checkout\Model;

use Lightning\Model\Object;
use Lightning\Tools\Template;

class Product extends Object {
    const TABLE = 'checkout_product';
    const PRIMARY_KEY = 'product_id';

    protected $__json_encoded_fields = ['options'];

    public function optionsSatisfied($options) {
        foreach ($this->options->options as $option => $settings) {
            if (!empty($settings->required) && empty($options[$option])) {
                return false;
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
        $template->set('product_id', $this->id);
        return $template->build(['options', 'Checkout'], true);
    }
}
