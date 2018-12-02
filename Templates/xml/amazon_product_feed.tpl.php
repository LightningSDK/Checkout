<?xml version="1.0" ?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amznenvelope.xsd">
    <Header>
        <DocumentVersion>1.01</DocumentVersion>
        <MerchantIdentifier><?= $sellerId; ?></MerchantIdentifier>
    </Header>
    <MessageType>Product</MessageType>
    <PurgeAndReplace>true</PurgeAndReplace>
    <?php $i = 1; foreach ($products as $product): ?>
        <?php
        /**
         * This will divide a product into multiple products for options amazon does not consider options like style.
         * If none of those options are available, the original product will be returned.
         */ ?>
        <?php foreach ($product->getAmazonProducts() as $knownOptions): ?>
            <Message>
                <?php
                /**
                 * This is the parent entry when there are multiple options, or the only entry if there are none.
                 */
                ?>
                <?php if (!empty($product->sku) && empty($product->options['amazon']['ignore'])): ?>
                    <MessageID><?= $i++; ?></MessageID>
                    <OperationType>Update</OperationType>
                    <Product>
                        <?php if ($product->hasOptions($knownOptions)) {
                                    $options = current($product->getAllOptionCombinations($knownOptions));
                            $nonAmazonVariations = $product->getNonAmazonVariation($options);
                        } else { $nonAmazonVariations = []; } ?>
                        <SKU><?= $product->sku; ?>-<?= \Lightning\Tools\Scrub::url(implode('-', $nonAmazonVariations)); ?></SKU>
                        <ProductTaxCode>A_GEN_NOTAX</ProductTaxCode>
                        <ProductType>Clothing</ProductType>
                        <DescriptionData>
                            <Title><?= $product->title; ?></Title>
                            <Brand><?= \Source\Model\Site::getInstance()->name; ?></Brand>
                            <Description><?= strip_tags($product->description); ?></Description>
                            <Manufacturer><?= \Source\Model\Site::getInstance()->name; ?></Manufacturer>
                            <?php foreach (explode(',', $product->keywords ?? '') as $keyword): if (!empty($keyword)): ?>
                                <SearchTerms><?= $keyword; ?></SearchTerms>
                            <?php endif; endforeach; ?>
                            <IsGiftWrapAvailable>false</IsGiftWrapAvailable>
                            <IsGiftMessageAvailable>false</IsGiftMessageAvailable>
                            <?php foreach ($product->options['amazon']['browse-node'] ?? [] as $node): ?>
                                <RecommendedBrowseNode><?= $node; ?></RecommendedBrowseNode>
                            <?php endforeach; ?>
                        </DescriptionData>
                        <ProductData>
                            <Home>
                                <?php if ($product->hasOptions($knownOptions)) : ?>
                                    <Parentage>variation-parent</Parentage>
                                    <VariationData>
                                        <VariationTheme><?php
                                            $options = current($product->getAllOptionCombinations($knownOptions));
                                            $amazonVariations = $product->getAmazonVariation($options);
                                            print implode('-', array_keys($amazonVariations)) ?></VariationTheme>
                                    <?php foreach ($amazonVariations as $key => $value): ?>
                                        <<?= $key; ?>><?= $value; ?></<?= $key; ?>>
                                    <?php endforeach; ?>
                                    </VariationData>
                                <?php endif; ?>
                                <?php if (!empty($product->options['material'])): ?>
                                    <Material><?= $product->options['material']; ?></Material>
                                <?php endif; ?>
                            </Home>
                        </ProductData>
                    </Product>
                <?php endif; ?>
            </Message>
            <?php foreach ($product->getAllOptionCombinations($knownOptions) as $variation): if (!empty($variation)): ?>
                <?php
                /**
                 * This is any child options.
                 */
                ?>
                <Message>
                    <MessageID><?= $i++; ?></MessageID>
                    <OperationType>Update</OperationType>
                    <Product>
                        <SKU><?= $product->sku; ?>-<?= \Lightning\Tools\Scrub::url(implode('-', $variation)); ?></SKU>
                        <ProductTaxCode>A_GEN_NOTAX</ProductTaxCode>
                        <ProductType>Clothing</ProductType>
                        <ProductData>
                            <Home>
                                <Parentage>child</Parentage>
                                <VariationData>
                                    <?php $amazonVariations = $product->getAmazonVariation($variation); ?>
                                <VariationTheme><?= implode('-', array_keys($amazonVariations)) ?></VariationTheme>
                                <?php foreach ($amazonVariations as $key => $value): ?>
                                    <<?= $key; ?>><?= $value; ?></<?= $key; ?>>
                                <?php endforeach; ?>
                                </VariationData>
                            </Home>
                        </ProductData>
                    </Product>
                </Message>
            <?php endif; endforeach; ?>
        <?php endforeach; ?>
    <?php endforeach; ?>
</AmazonEnvelope>
