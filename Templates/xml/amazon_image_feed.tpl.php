<?xml version="1.0" ?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amznenvelope.xsd">
    <Header>
        <DocumentVersion>1.01</DocumentVersion>
        <MerchantIdentifier><?= $sellerId; ?></MerchantIdentifier>
    </Header>
    <MessageType>ProductImage</MessageType>
    <PurgeAndReplace>true</PurgeAndReplace>
    <?php $i = 1; foreach ($products as $product): ?>
        <?php
        /**
         * This will divide a product into multiple products for options amazon does not consider options like style.
         * If none of those options are available, the original product will be returned.
         */ ?>
        <?php foreach ($product->getAmazonProducts() as $knownOptions): ?>
            <?php foreach ($product->getAllOptionCombinations($knownOptions) as $variation): if (!empty($variation)): ?>
                <?php $images = $product->getOptionForSettings('image', $variation);
                if (is_string($images)) {$images = [$images];}
                foreach ($images as $image): ?>
                <Message>
                    <MessageID><?= $i++; ?></MessageID>
                    <OperationType>Update</OperationType>
                    <ProductImage>
                        <SKU><?= $product->sku; ?>-<?= \Lightning\Tools\Scrub::url(implode('-', $variation)); ?></SKU>
                        <ImageType><?= $main ? 'Main' : 'Alternate'; ?></ImageType>
                        <ImageLocation><?= \Lightning\Model\URL::getAbsolute($image); ?></ImageLocation>
                    </ProductImage>
                </Message>
                <?php endforeach; ?>
            <?php endif; endforeach; ?>
        <?php endforeach; ?>
    <?php endforeach; ?>
</AmazonEnvelope>
