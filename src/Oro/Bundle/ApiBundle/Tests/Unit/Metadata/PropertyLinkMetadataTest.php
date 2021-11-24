<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\MetaAttributeMetadata;
use Oro\Bundle\ApiBundle\Metadata\PropertyLinkMetadata;

class PropertyLinkMetadataTest extends \PHPUnit\Framework\TestCase
{
    public function testClone()
    {
        $linkMetadata = new PropertyLinkMetadata('testField');
        $linkMetadata->addMetaProperty(new MetaAttributeMetadata('metaProperty1', 'string'));

        $linkMetadataClone = clone $linkMetadata;

        self::assertEquals($linkMetadata, $linkMetadataClone);
    }

    public function testToArray()
    {
        $linkMetadata = new PropertyLinkMetadata('testField');
        $linkMetadata->addMetaProperty(new MetaAttributeMetadata('metaProperty1', 'string'));

        self::assertEquals(
            [
                'property_path'   => 'testField',
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
        $linkMetadata = new PropertyLinkMetadata('testField');

        self::assertEquals(
            [
                'property_path' => 'testField'
            ],
            $linkMetadata->toArray()
        );
    }

    public function testGetHrefWhenPropertyDoesNotExist()
    {
        $linkMetadata = new PropertyLinkMetadata('testField');

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::once())
            ->method('tryGetValue')
            ->with('testField')
            ->willReturn(false);

        self::assertNull($linkMetadata->getHref($dataAccessor));
    }

    public function testGetHrefWhenPropertyValueIsEmptyString()
    {
        $linkMetadata = new PropertyLinkMetadata('testField');

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::once())
            ->method('tryGetValue')
            ->with('testField')
            ->willReturnCallback(function ($propertyPath, &$value) {
                $value = '';

                return true;
            });

        self::assertNull($linkMetadata->getHref($dataAccessor));
    }

    public function testGetHrefWhenPropertyValueIsNotEmpty()
    {
        $linkMetadata = new PropertyLinkMetadata('testField');

        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $dataAccessor->expects(self::once())
            ->method('tryGetValue')
            ->with('testField')
            ->willReturnCallback(function ($propertyPath, &$value) {
                $value = 'testUrl';

                return true;
            });

        self::assertEquals('testUrl', $linkMetadata->getHref($dataAccessor));
    }

    public function testMetaProperties()
    {
        $linkMetadata = new PropertyLinkMetadata('testField');
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
