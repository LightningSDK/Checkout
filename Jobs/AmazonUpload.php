<?php

namespace lightningsdk\checkout\Jobs;

use lightningsdk\core\Jobs\Job;
use lightningsdk\core\Tools\Communicator\RestClient;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Template;
use lightningsdk\checkout\Model\Product;

class AmazonUpload extends Job {

    const NAME = 'Amazon Product Sync';

    protected $host = 'mws.amazonservices.com';
    protected $client;

    public function __construct() {
        $this->client = new RestClient('https://' . $this->host);
        $this->client->setHeader('Content-Type', 'text/xml');
    }

    public function execute($job) {
//        $this->sendProductList($job);
        $this->getSubmissionStatus('50143017878');
//        $this->sendProductRelationships($job);
//        $this->getSubmissionStatus('50122017873');
//        $this->sendProductImages($job);
//        $this->getSubmissionStatus('50122017873');
//        $this->sendProductPrices($job);
//        $this->getSubmissionStatus('50124017873');
        exit;
    }

    public function getSubmissionStatus($submissionId) {

        $query = $this->getBaseQuery();
        $query['Action'] = 'GetFeedSubmissionResult';
        $query['FeedSubmissionId'] = $submissionId;

        $query['Signature'] = $this->getSignature('/Feeds/2009-01-01', $query);
        $this->client->callPost('/Feeds/2009-01-01?' . http_build_query($query));
        print_r($this->client->getRaw());
    }

    public function sendProductList() {
        // Build the feed
        $template = $this->getTemplate();
        $template->set('products', $this->getProducts());
        $content = $template->render(['xml/amazon_product_feed', 'Checkout'], true);

        return $this->submitFeed($content, '_POST_PRODUCT_DATA_');
    }

    public function sendProductRelationships() {
        // Build the feed
        $template = $this->getTemplate();
        $template->set('products', $this->getProducts());
        $content = $template->render(['xml/amazon_product_relationship_feed', 'Checkout'], true);

        return $this->submitFeed($content, '_POST_PRODUCT_DATA_');
    }

    public function sendProductImages() {
        // Build the feed
        $template = $this->getTemplate();
        $template->set('products', $this->getProducts());
        $content = $template->render(['xml/amazon_image_feed', 'Checkout'], true);

        return $this->submitFeed($content, '_POST_PRODUCT_IMAGE_DATA_');
    }

    public function sendProductPrices() {
        // Build the feed
        $template = $this->getTemplate();
        $template->set('products', $this->getProducts());
        $content = $template->render(['xml/amazon_price_feed', 'Checkout'], true);

        return $this->submitFeed($content, '_POST_PRODUCT_PRICING_DATA_');
    }

    protected function getTemplate() {
        $template = new Template();
        $template->setDebug(false);
        $template->set('sellerId', Configuration::get('modules.checkout.amazon.seller_id'));
        return $template;
    }

    protected function getProducts() {
        $products = Product::loadAll(['sku' => ['IS NOT NULL'], 'product_id' => 164]);
        if (empty($products)) {
            throw new \Exception('No products found');
        }
        return $products;
    }

    protected function submitFeed($content, $feedType) {
        $this->client->clearRequestVars();

        print $content;
        $this->client->setBody($content);

        // Main request header information
        $query = $this->getBaseQuery();
        $query['Action'] = 'SubmitFeed';
        $query['FeedType'] = $feedType;
        $query['ContentMD5Value'] = base64_encode(md5($content, true));

        $query['Signature'] = $this->getSignature('/Feeds/2009-01-01', $query);
        $this->client->callPost('/Feeds/2009-01-01?' . http_build_query($query));

        $body = $this->client->getRaw();
        $xml = simplexml_load_string($body);
        $submissionId = (string) $xml->SubmitFeedResult->FeedSubmissionInfo->FeedSubmissionId;
        $this->out('Submitted feed: ' . $submissionId);
        return $submissionId;
    }

    protected function getBaseQuery() {
        return [
            'AWSAccessKeyId' => Configuration::get('modules.checkout.amazon.access_key_id'),
            'SignatureMethod' => 'HmacSHA256',
            'SignatureVersion' => '2',
            'Timestamp' => gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time()),
            'Version' => '2009-01-01',
            'SellerId' => Configuration::get('modules.checkout.amazon.seller_id'),
        ];
    }

    protected function getSignature($path, $requestVars) {
        uksort($requestVars, 'strcmp');
        $content = implode("\n", [
            'POST',
            $this->host,
            $path,
            http_build_query($requestVars)
        ]);

        return base64_encode(hash_hmac('sha256', $content, Configuration::get('modules.checkout.amazon.secret_key'), true));
    }
}
