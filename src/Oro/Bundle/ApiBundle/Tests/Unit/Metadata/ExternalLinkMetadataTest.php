<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\ExternalLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaAttributeMetadata;

class ExternalLinkMetadataTest extends \PHPUnit\Framework\TestCase
{
    public function testClone()
    {
        $linkMetadata = new ExternalLinkMetadata(
            'urlTemplate',
            ['key1' => 'value1'],
            ['key2' => 'value2']
        );
        $linkMetadata->addMetaProperty(new MetaAttributeMetadata('metaProperty1', 'string'));

        $linkMetadataClone = clone $linkMetadata;

        self::assertEquals($linkMetadata, $linkMetadataClone);
    }

    public function testToArray()
    {
        $linkMetadata = new ExternalLinkMetadata(
            'testUrlTemplate',
            ['key1' => 'value1'],
            ['key2' => 'value2']
        );
        $linkMetadata->addMetaProperty(new MetaAttributeMetadata('metaProperty1', 'string'));

        self::assertEquals(
            [
                'url_template'    => 'testUrlTemplate',
                'url_params'      => ['key1' => 'value1'],
                'default_params'  => ['key2' => 'value2'],
                'meta_properties' => [
                    'metaProperty1' => [
                        'data_type' => 'string'
                    ]
                ]
            ],
            $linkMetadata->toArray()
        );
    }

    public function testToArrayWithRequiredPropertiesOnly()
    {
        $linkMetadata = new ExternalLinkMetadata('testUrlTemplate');

        self::assertEquals(
            [
                'url_template' => 'testUrlTemplate'
            ],
            $linkMetadata->toArray()
        );
    }

    public function testGetHrefWithoutUrlParams()
    {
        $urlTemplate = 'http://test.com/api/{version}/{resource}?filter={filter}';
        $linkMetadata = new ExternalLinkMetadata($urlTemplate);

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::never())
            ->method('tryGetValue');

        self::assertEquals(
            $urlTemplate,
            $linkMetadata->getHref($dataAccessor)
        );
    }

    public function testGetHrefWhenAllUrlParamsAreResolved()
    {
        $linkMetadata = new ExternalLinkMetadata(
            'http://test.com/api/{version}/{resource}?filter={filter}',
            ['resource' => DataAccessorInterface::ENTITY_TYPE, 'filter' => null]
        );

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::exactly(2))
            ->method('tryGetValue')
            ->willReturnCallback(function ($propertyPath, &$value) {
                $hasValue = false;
                if (DataAccessorInterface::ENTITY_TYPE === $propertyPath) {
                    $value = 'entity';
                    $hasValue = true;
                } elseif ('filter' === $propertyPath) {
                    $value = 123;
                    $hasValue = true;
                }

                return $hasValue;
            });

        self::assertEquals(
            'http://test.com/api/{version}/entity?filter=123',
            $linkMetadata->getHref($dataAccessor)
        );
    }

    public function testGetHrefWhenOnlyPartOfUrlParamsAreResolvedButThereAreDefaultValuesInDefaultParams()
    {
        $linkMetadata = new ExternalLinkMetadata(
            'http://test.com/api/{version}/{resource}?filter={filter}',
            ['resource' => DataAccessorInterface::ENTITY_TYPE, 'filter' => null],
            ['key1' => 'value1', 'filter' => 'defaultFilter']
        );

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::exactly(2))
            ->method('tryGetValue')
            ->willReturnCallback(function ($propertyPath, &$value) {
                $hasValue = false;
                if (DataAccessorInterface::ENTITY_TYPE === $propertyPath) {
                    $value = 'entity';
                    $hasValue = true;
                }

                return $hasValue;
            });

        self::assertEquals(
            'http://test.com/api/{version}/entity?filter=defaultFilter',
            $linkMetadata->getHref($dataAccessor)
        );
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\LinkHrefResolvingFailedException
     * @expectedExceptionMessage Cannot build URL for a link. Missing Parameters: filter,version.
     */
    public function testGetHrefWhenOnlyPartOfUrlParamsAreResolved()
    {
        $linkMetadata = new ExternalLinkMetadata(
            'http://test.com/api/{version}/{resource}?filter={filter}',
            ['resource' => DataAccessorInterface::ENTITY_TYPE, 'filter' => null, 'version' => '_.version']
        );

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::exactly(3))
            ->method('tryGetValue')
            ->willReturnCallback(function ($propertyPath, &$value) {
                $hasValue = false;
                if (DataAccessorInterface::ENTITY_TYPE === $propertyPath) {
                    $value = 'entity';
                    $hasValue = true;
                }

                return $hasValue;
            });

        self::assertNull($linkMetadata->getHref($dataAccessor));
    }

    public function testMetaProperties()
    {
        $linkMetadata = new ExternalLinkMetadata('urlTemplate');
        self::assertCount(0, $linkMetadata->getMetaProperties());
        self::assertFalse($linkMetadata->hasMetaProperty('unknown'));
        self::assertNull($linkMetadata->getMetaProperty('unknown'));

        $metaProperty1 = new MetaAttributeMetadata('metaProperty1', 'string');
        self::assertSame($metaProperty1, $linkMetadata->addMetaProperty($metaProperty1));
        $metaProperty2 = new MetaAttributeMetadata('metaProperty2', 'string');
        self::assertSame($metaProperty2, $linkMetadata->addMetaProperty($metaProperty2));
        self::assertCount(2, $linkMetadata->getMetaProperties());

        self::assertTrue($linkMetadata->hasMetaProperty('metaProperty1'));
        self::assertSame($metaProperty1, $linkMetadata->getMetaProperty('metaProperty1'));

        $linkMetadata->removeMetaProperty('metaProperty1');
        self::assertCount(1, $linkMetadata->getMetaProperties());
        self::assertFalse($linkMetadata->hasMetaProperty('metaProperty1'));
        self::assertTrue($linkMetadata->hasMetaProperty('metaProperty2'));

        $linkMetadata->removeMetaProperty('metaProperty2');
        self::assertCount(0, $linkMetadata->getMetaProperties());
        self::assertFalse($linkMetadata->hasMetaProperty('metaProperty2'));
    }
}
