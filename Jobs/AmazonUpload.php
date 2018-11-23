<?php

namespace Modules\Checkout\Jobs;

use Lightning\Jobs\Job;
use Lightning\Tools\Communicator\RestClient;
use Lightning\Tools\Configuration;
use Lightning\Tools\Template;
use Modules\Checkout\Model\Product;

class AmazonUpload extends Job {

    const NAME = 'Amazon Product Sync';

    protected $host = 'mws.amazonservices.com';

    public function execute($job) {
//        $submissionId = $this->sendProductList($job);
//        sleep(60);
        $submissionId = '50017017855';
        $this->getSubmissionStatus($submissionId);
    }

    public function getSubmissionStatus($submissionId) {
        $client = new RestClient('https://' . $this->host);
        $client->setHeader('Content-Type', 'text/xml');

        $query = $this->getBaseQuery();
        $query['Action'] = 'GetFeedSubmissionResult';
        $query['FeedSubmissionId'] = $submissionId;

        $query['Signature'] = $this->getSignature('/Feeds/2009-01-01', $query);
        $client->callPost('/Feeds/2009-01-01?' . http_build_query($query));
        print_r($client->getRaw());
    }

    public function sendProductList() {
        $client = new RestClient('https://' . $this->host);
        $client->setHeader('Content-Type', 'text/xml');

        // Build the feed
        $template = new Template();
        $template->setDebug(false);
        $products = Product::loadAll();
        $template->set('products', $products);
        $template->set('sellerId', Configuration::get('modules.checkout.amazon.seller_id'));
        $content = $template->render(['xml/amazon_feed', 'Checkout'], true);
        $client->setBody($content);
        print $content;

        // Main request header information
        $query = $this->getBaseQuery();
        $query['Action'] = 'SubmitFeed';
        $query['FeedType'] = '_POST_PRODUCT_DATA_';
        $query['ContentMD5Value'] = base64_encode(md5($content, true));

        $query['Signature'] = $this->getSignature('/Feeds/2009-01-01', $query);
        $client->callPost('/Feeds/2009-01-01?' . http_build_query($query));

        $body = $client->getRaw();
        $xml = simplexml_load_string($body);
        return (string) $xml->SubmitFeedResult->FeedSubmissionInfo->FeedSubmissionId;
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


//        $client->set('FeedContent', $content);
//        $client->set('FeedType', '_POST_PRODUCT_DATA_');
//        $client->set('MWSAuthToken', '');
//        $client->set('MarketplaceIdList.Id.1', '');
//        $client->set('ContentMD5Value', md5($content));
//        $client->setHeader('Content-MD5', md5($content));
