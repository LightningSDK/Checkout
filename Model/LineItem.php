<?php

namespace lightningsdk\checkout\Model;

use Lightning\Model\BaseObject;
use Lightning\Tools\Database;
use Lightning\View\HTMLEditor\Markup;

class LineItem extends BaseObject {
    const TABLE = 'checkout_order_item';
    const PRIMARY_KEY = 'checkout_order_item_id';

    protected $__json_encoded_fields = ['options' => ['type' => 'array', 'base64' => true]];

    protected $formattedOptions;

    /**
     * @var Product
     *
     * A reference to the product data.
     */
    protected $product;

    public function __set($var, $value) {
        // If the value is actually changing.
        if ($this->__data[$var] != $value) {
            // Clear out the formatted options.
            $this->formattedOptions;
            parent::__set($var, $value);
        }
    }

    /**
     * @return Product
     */
    public function getProduct() {
        $this->loadProduct();
        return $this->product;
    }

    public function getPrice() {
        $this->loadProduct();
        $values = $this->product->getAggregateOptions($this);
        return !empty($values['price']) ? $values['price'] : $this->product->price;
    }

    public function setProduct($product) {
        $this->product = $product;
    }

    protected function loadProduct() {
        if (empty($this->product) && !empty($this->product_id)) {
            $this->product = Product::loadByID($this->product_id);
        }
    }

    /**
     * Load a list of line item objects wth all their product data and options.
     *
     * @param $order_id
     * @return array
     */
    public static function loadAllByOrderID($order_id) {
        // Load the line items.
        $db = Database::getInstance();
        $data = $db->selectAll(self::TABLE, ['order_id' => $order_id]);

        // Load associated product data.
        $product_ids = array_unique(array_column($data, 'product_id'));
        $products = Product::loadAll(['product_id' => ['IN', $product_ids]], [], '', true);

        // Convert any items found into objects.
        $results = [];
        if ($data) {
            foreach ($data as $row) {
                $item = new static($row);
                $item->setProduct($products[$item->product_id]);
                $results[] = $item;
            }
        }
        return $results;
    }

    /**
     * Marke the line item as fulfilled.
     */
    public function markFulfilled() {
        $this->fulfilled = time();
        $this->save();
    }

    public function getHTMLFormattedOptions() {
        // Render the output if that has not been done yet.
        if ($this->formattedOptions === null) {
            if (!empty($this->product->options->option_formatting_user)) {
                $this->formattedOptions = Markup::render(
                    $this->product->options->option_formatting_user,
                    $this->options
                );
            } else {
                $options = $this->options;
                $this->formattedOptions = '';
                foreach ($options as $option => $value) {
                    $this->formattedOptions .= $option . ': <strong>' . $value . '</strong> ';
                }
            }
        }

        // Always return an empty string if there is nothing there.
        return $this->formattedOptions ?: '';
    }

    public function getAggregateOption($option, $default = null) {
        $this->loadProduct();
        $aggregate_options = $this->product->getAggregateOptions($this);
        if (!empty($aggregate_options[$option])) {
            return $aggregate_options[$option];
        } elseif (!empty($this->product->$option)) {
            return $this->product->$option;
        } else {
            return $default;
        }
    }
}
