<?php

namespace lightningsdk\checkout\View;

use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Scrub;
use lightningsdk\core\Tools\Template;
use lightningsdk\core\View\HTML;
use lightningsdk\checkout\Model\Category as CategoryModel;

class Category {

    /**
     * @param $options
     *   string text - The text to display in the the button. Default: 'Buy Now'
     *   integer product-id - The product ID to add to the cart on checkout
     *   boolean create-customer - Whether to save the user's card information for rebilling. Default: false.
     *   string redirect - A redirection URL after the purchase is complete.
     *   string class - A string of classes to be added to the button. Default: 'button red'.
     *
     * @param $vars
     * @return string
     *
     * Renders a product or list of products with image.
     *
     * TODO: This should call the Checkout::renderMarkup() for rendering the button.
     */
    public static function renderMarkup($options, $vars) {


        Checkout::init();

        if (!empty($options['categories'])) {
            $category_ids = explode(',', $options['categories']);
            $categories = CategoryModel::loadMultipleByIds($category_ids);
        } else {
            $categories = CategoryModel::loadAll();
        }

        $template = new Template();
        $template->setData([
            'categories' => $categories,
        ]);
        return $template->render(['components/category', 'lightningsdk/checkout'],  true);

        $output = '';

        $config = Configuration::get('modules.checkout');
    }
}
