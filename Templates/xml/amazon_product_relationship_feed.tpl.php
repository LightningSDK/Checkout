<?xml version="1.0" ?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
    <Header>
        <DocumentVersion>1.01</DocumentVersion>
        <MerchantIdentifier>A1GBQ76J5TVBOY</MerchantIdentifier>
    </Header>
    <MessageType>Relationship</MessageType>
    <PurgeAndReplace>false</PurgeAndReplace>

    <?php $i = 1; foreach ($products as $product): ?>
        <?php
        /**
         * This will divide a product into multiple products for options amazon does not consider options like style.
         * If none of those options are available, the original product will be returned.
         */ ?>
        <?php foreach ($product->getAmazonProducts() as $knownOptions): ?>
        <Message>
            <MessageID><?= $i++; ?></MessageID>
            <?php
            /**
             * This is the parent entry when there are multiple options, or the only entry if there are none.
             */
            ?>
            <?php if (!empty($product->sku) && empty($product->options['amazon']['ignore'])): ?>
                <Relationship>
                    <?php if ($product->hasOptions($knownOptions)) {
                        $options = current($product->getAllOptionCombinations($knownOptions));
                        $nonAmazonVariations = $product->getNonAmazonVariation($options);
                    } else { $nonAmazonVariations = []; } ?>
                    <ParentSKU><?= $product->sku; ?>-<?= \lightningsdk\core\Tools\Scrub::url(implode('-', $nonAmazonVariations)); ?></ParentSKU>

                    <?php
                    /**
                     * This is any child options.
                     */
                    ?>
                    <?php foreach ($product->getAllOptionCombinations($knownOptions) as $variation): if (!empty($variation)): ?>
                        <Relation>
                            <SKU><?= $product->sku; ?>-<?= \lightningsdk\core\Tools\Scrub::url(implode('-', $variation)); ?></SKU>
                            <Type>Variation</Type>
                        </Relation>
                    <?php endif; endforeach; ?>
                </Relationship>
            <?php endif; ?>
        </Message>
        <?php endforeach; ?>
    <?php endforeach; ?>
</AmazonEnvelope>
