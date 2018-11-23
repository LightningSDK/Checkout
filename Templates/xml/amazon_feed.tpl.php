<?xml version="1.0" ?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amznenvelope.xsd">
    <Header>
        <DocumentVersion>1.01</DocumentVersion>
        <MerchantIdentifier><?= $sellerId; ?></MerchantIdentifier>
    </Header>
    <MessageType>Product</MessageType>
    <PurgeAndReplace>true</PurgeAndReplace>
    <Message>
        <?php $i = 1; foreach ($products as $product): ?>
        <MessageID><?= $i++; ?></MessageID>
        <OperationType>Update</OperationType>
        <Product>
            <SKU><?= $product->sku; ?></SKU>
            <ProductTaxCode>A_GEN_NOTAX</ProductTaxCode>
            <DescriptionData>
                <Title><?= $product->title; ?></Title>
                <Brand><?= \Source\Model\Site::getInstance()->name; ?></Brand>
                <Description><?= $product->description; ?></Description>
                <Manufacturer><?= \Source\Model\Site::getInstance()->name; ?></Manufacturer>
                <? foreach (explode(',', $product->keywords ?? '') as $keyword): ?>
                <SearchTerms><?= $keyword; ?></SearchTerms>
                <? endforeach; ?>
                <IsGiftWrapAvailable>false</IsGiftWrapAvailable>
                <IsGiftMessageAvailable>false</IsGiftMessageAvailable>
                <?php foreach ($product->options['amazon']['browse-node'] ?? [] as $node): ?>
                <RecommendedBrowseNode><?= $node; ?></RecommendedBrowseNode>
                <?php endforeach; ?>
            </DescriptionData>
            <ProductData>
                <Home>
                    <Parentage>variation-parent</Parentage>
                    <VariationData>
                        <?php foreach ($product->getAllOptionCombinations() as $variation): ?>
                        <VariationTheme><?= implode('-', array_keys($variation)) ?></VariationTheme>
                        <?php if (!empty($variation['Color'])): ?>
                            <Color><?= $variation['Color']; ?></Color>
                        <?php endif; ?>
                        <?php if (!empty($variation['Size'])): ?>
                            <Size><?= $variation['Size']; ?></Size>
                        <?php endif; ?>
                        <?php if (!empty($variation['Style'])): ?>
                            <Style><?= $variation['Style']; ?></Style>
                        <?php endif; ?>
                        <Price><?= $product->getOptionForSettings('price', $variation); ?></Price>
                        <?php endforeach; ?>
                        <?php $images = $product->getOptionForSettings('image', $variation);
                        if (is_string($images)) {$images = [$images];}
                        foreach ($images as $image):?>
                        <Image><?= \Lightning\Model\URL::getAbsolute($image) ?></Image>
                        <?php endforeach; ?>
                    </VariationData>
                <?php if (!empty($product->options['material'])): ?>
                    <Material><?= $product->options['material']; ?></Material>
                <?php endif; ?>
                </Home>
            </ProductData>
        </Product>
        <?php endforeach; ?>
    </Message>
</AmazonEnvelope>