<?php

namespace Modules\Checkout\Model;

use Exception;
use Lightning\Model\Object;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Image;
use Lightning\Tools\Template;

class ProductOverridable extends Object {
    const TABLE = 'checkout_product';
    const PRIMARY_KEY = 'product_id';

    const IMAGE_LISTING = 'listing-image';
    const IMAGE_OG = 'og-image';
    const IMAGE_MAIN = 'image';

    protected $__json_encoded_fields = ['options' => ['type' => 'array']];
    protected $categoryObj;
    protected $compiledOptions;
    protected $relativeValueFields = ['price', 'cost'];

    public function __get($var) {
        if ($var == 'options') {
            // Options has a custom override
            if ($this->compiledOptions === null) {
                // Get the options from the classes.
                $classes = Database::getInstance()->selectColumnQuery([
                    'select' => 'checkout_product_class.options',
                    'from' => 'checkout_product_product_class',
                    'join' => [
                        'join' => 'checkout_product_class',
                        'using' => 'product_class_id',
                    ],
                    'where' => ['product_id' => $this->id]
                ]);
                foreach ($classes as $class_options) {
                    $options[] = json_decode($class_options, true);
                }
                // Overwrite with anything specific to this product.
                $options[] = parent::__get('options');
                // Save the output for multiple requests.
                $this->compiledOptions = array_replace_recursive(...$options);
            }
            return $this->compiledOptions;
        }
        return parent::__get($var);
    }

    public static function loadByURL($url) {
        $data = Database::getInstance()->selectRow(self::TABLE, ['url' => ['LIKE', $url]]);
        if (!empty($data)) {
            return new static($data);
        } else {
            return null;
        }
    }

    public static function loadMultipleByIds($product_ids) {
        return self::loadByQuery(
            [
                'where' => [
                    'product_id' => ['IN', $product_ids],
                    'active' => 1
                ],
                'order_by' => [
                    'product_id' => $product_ids,
                ]
            ]
        );
    }

    public static function getSitemapUrls() {
        $urls = [];

        // Load the pages.
        $web_root = Configuration::get('web_root');
        $products = static::loadAll(['active' => 1]);

        foreach($products as $p) {
            $urls[] = [
                'loc' => $web_root . "/store/{$p->url}",
                'lastmod' => date('Y-m-d', time()),
                'changefreq' => 'monthly',
                'priority' => 90 / 100,
            ];
        }

        return $urls;
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

    public function getBreadcrumbs() {
        if (!empty($this->options['breadcrumbs'])) {
            $breadcrumbs = $this->options['breadcrumbs'];
        } else {
            $cat = $this->getCategory();
            if (!empty($cat)) {
                $breadcrumbs = $this->getCategory()->getBreadcrumbs(false);
            }
        }
        $breadcrumbs['#current'] = $this->title;
        return $breadcrumbs;
    }

    /**
     * @return Category
     */
    public function getCategory() {
        if (!empty($this->categoryObj)) {
            return $this->categoryObj;
        }
        if (!empty($this->category_id)) {
            return Category::loadByID($this->category_id);
        }
        return null;
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

    public function getImage($type = self::IMAGE_LISTING) {
        $image = null;
        try {
            $options = $this->options;
            array_walk_recursive($options, function($val, $key) use (&$image, $type) {
                if (empty($key)) {
                    return;
                }
                switch ($key) {
                    case self::IMAGE_OG:
                    case self::IMAGE_MAIN:
                        $image = $val;
                        if ($key == $type) {
                            throw new Exception('Complete');
                        }
                        break;
                    case self::IMAGE_LISTING:
                        $image = $val;
                        if ($key == $type) {
                            throw new Exception('Complete');
                        }
                }
            });
        } catch (Exception $e) {};

        if (Configuration::get('modules.imageManager')) {
            $size = 1000;
            if ($type == self::IMAGE_LISTING) {
                $size = 250;
            }

            // Image manager is enabled, so use the image manager version
            return '/image?' . http_build_query([
                'i' => $image,
                's' => $size,
                'f' => Image::FORMAT_JPG,
            ]);
        }

        return $image;
    }

    public function aggregateOptions($selected_options) {
        $options = [
            'price' => floatval($this->price),
        ];
        // TODO: This does not take into account options on the same level.
        $options += $this->options;
        while (!empty($options['options'])) {
            // Iterate over the options
            $child_options = $options['options'];
            unset($options['options']);
            foreach ($child_options as $option_name => $settings) {
                // If the option is set, the child options will override the parent options.
                if (!empty($selected_options[$option_name])) {
                    $selected_value = $selected_options[$option_name];
                    if (!empty($settings['values'][$selected_value])) {
                        $optionValues = $settings['values'][$selected_value];
                        // If relative values are present ...
                        foreach ($this->relativeValueFields as $field) {
                            if (isset($optionValues[$field])) {
                                if (substr($optionValues[$field], 0, 1) == '+') {
                                    $optionValues[$field] = $options[$field] + floatval(substr($optionValues[$field], 1));
                                } elseif (substr($optionValues[$field], 0, 1) == '+') {
                                    $optionValues[$field] = $options[$field] - floatval(substr($optionValues[$field], 1));
                                }
                            }
                        }
                        $options = $optionValues + $options;
                    }
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
                return $handler::printPlan($this->options['subscription']);
            }
        }
    }

    public function isSubscription() {
        return !empty($this->options['subscription']);
    }
}
