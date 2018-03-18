<?php

namespace Modules\Checkout\Pages;

use Lightning\Tools\Request;
use Lightning\Tools\Template;
use Modules\Checkout\Model\Product as ProductModel;
use Lightning\View\Widget;

class ProductWidget extends Widget {
    protected $page = ['widget', 'Checkout'];
    protected $maxProducts = 4;

    public function getBody() {
        parent::getBody();
        $products = $this->getProducts();
        $template = Template::getInstance();
        $template->set('products', $products);
    }

    protected function getProducts() {
        $products = Request::get('products', Request::TYPE_ARRAY, Request::TYPE_INT);

        if (!empty($products)) {
            $products = ProductModel::loadAll(['product_id' => ['IN', $products], 'active' => 1], [], 'ORDER BY RAND()');
            return $products;
        }

        $categories = Request::get('categories', Request::TYPE_EXPLODE, Request::TYPE_INT);
        if (!empty($categories)) {
            $products = ProductModel::loadByQuery([
                'select' => 'checkout_product.*',
                'from' => 'checkout_category',
                'join' => [
                    'join' => 'checkout_product',
                    'using' => 'category_id'
                ],
                'where' => [
                    'category_id' => ['IN', $categories],
                    'active' => 1,
                ],
                'order_by' => [
                    'rand' => ['expression' => 'RAND()']
                ],
                'limit' => $this->maxProducts,
            ]);
            return $products;
        }

        // If no products or categories were specified, use random products
        $products = ProductModel::loadByQuery([
            'from' => 'checkout_product',
            'where' => [
                'active' => 1,
            ],
            'order_by' => [
                'rand' => ['expression' => 'RAND()']
            ],
            'limit' => $this->maxProducts,
        ]);
        return $products;
    }
}
