<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Engine\TextFilteredObjectMapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

class TextFilteredObjectMapperTest extends ObjectMapperTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->mapper = new TextFilteredObjectMapper($this->dispatcher, $this->mappingConfig);
        $this->mapper->setMappingProvider($this->mapperProvider);
        $this->mapper->setPropertyAccessor(PropertyAccess::createPropertyAccessor());
        $this->mapper->setHtmlTagHelper($this->htmlTagHelper);
    }

    /**
     * @todo Overwritten due to ORM limitations, resolved here: https://magecore.atlassian.net/browse/BB-12955
     */
    public function testAllTextLimitation()
    {
        // create a product name exceeding the 256 length limitation
        $productName        = 'QJfPB2teh0ukQN46FehTdiMRMMGGlaNvQvB4ymJq49zUWidBOhT9IzqNyPhYvchY1234' .
                              'QJfPB2teh0ukQN46FehTdiMRMMGGlaNvQvB4ymJq49zUWidBOhT9IzqNyPhYvchY1234' .
                              'QJfPB2teh0ukQN46FehTdiMRMMGGlaNvQvB4ymJq49zUWidBOhT9IzqNyPhYvchY1234' .
                              'QJfPB2teh0ukQN46FehTdiMRMMGGlaNvQvB4ymJq49zUWidBOhT9IzqNyPhYvchY1234' .
                              'QJfPB2teh0ukQN46FehTdiMRMMGGlaNvQvB4ymJq49zUWidBOhT9IzqNyPhYvchY1234' .
                              ' ';
        $expectedProductName = 'zUWidBOhT9IzqNyPhYvchY QJfPB2teh0ukQ';
        $productName .= $expectedProductName;
        $productDescription = 'description';
        $manufacturerName   = $this->product->getManufacturer()->getName();

        $allData = sprintf('%s %s %s', $expectedProductName, $productDescription, $manufacturerName);
        $allTextData = sprintf('%s %s %s', $expectedProductName, $productDescription, $manufacturerName);

        $expectedMapping = [
            'text' => $this->clearTextData(
                [
                    'name'                       => $expectedProductName,
                    'description'                => $productDescription,
                    'manufacturer'               => $manufacturerName,
                    'all_data'                   => $allData,
                    Indexer::TEXT_ALL_DATA_FIELD => $allTextData
                ]
            ),
            'decimal' => [
                'price' => $this->product->getPrice(),
            ],
            'integer' => [
                'count' => $this->product->getCount(),
            ]
        ];

        $this->product
            ->setName($productName)
            ->setDescription($productDescription);

        $this->assertEquals($expectedMapping, $this->mapper->mapObject($this->product));
    }

    /**
     * {@inheritdoc}
     */
    protected function clearTextData(array $fields)
    {
        foreach ($fields as $name => &$value) {
            $value = str_replace(['<p>', '</p>'], ['', ''], $value);
        }

        return $fields;
    }
}
