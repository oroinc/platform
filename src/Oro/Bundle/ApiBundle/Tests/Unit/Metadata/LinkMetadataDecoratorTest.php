<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\LinkMetadataDecorator;
use Oro\Bundle\ApiBundle\Metadata\LinkMetadataInterface;
use Oro\Bundle\ApiBundle\Metadata\MetaAttributeMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LinkMetadataDecoratorTest extends TestCase
{
    private LinkMetadataInterface&MockObject $linkMetadata;
    private LinkMetadataDecorator $decorator;

    #[\Override]
    protected function setUp(): void
    {
        $this->linkMetadata = $this->createMock(LinkMetadataInterface::class);
        $this->decorator = new LinkMetadataDecoratorStub($this->linkMetadata);
    }

    public function testClone(): void
    {
        $decoratorClone = clone $this->decorator;

        self::assertEquals($this->decorator, $decoratorClone);
        self::assertEquals($this->decorator->toArray(), $decoratorClone->toArray());
    }

    public function testToArray(): void
    {
        $linkData = ['key' => 'value'];
        $this->linkMetadata->expects(self::once())
            ->method('toArray')
            ->willReturn($linkData);

        $result = $this->decorator->toArray();
        self::assertArrayHasKey('decorator', $result);
        unset($result['decorator']);
        self::assertEquals($linkData, $result);
    }

    public function testGetHref(): void
    {
        $href = 'http://test.com/api/resource';
        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $this->linkMetadata->expects(self::once())
            ->method('getHref')
            ->with(self::identicalTo($dataAccessor))
            ->willReturn($href);

        self::assertEquals($href, $this->decorator->getHref($dataAccessor));
    }

    public function testMetaProperties(): void
    {
        $metaProperties = ['test' => new MetaAttributeMetadata('test')];
        $this->linkMetadata->expects(self::once())
            ->method('getMetaProperties')
            ->willReturn($metaProperties);

        self::assertEquals($metaProperties, $this->decorator->getMetaProperties());
    }

    public function testHasMetaProperty(): void
    {
        $result = true;
        $metaPropertyName = 'test';
        $this->linkMetadata->expects(self::once())
            ->method('hasMetaProperty')
            ->with($metaPropertyName)
            ->willReturn($result);

        self::assertSame($result, $this->decorator->hasMetaProperty($metaPropertyName));
    }

    public function testGetMetaProperty(): void
    {
        $metaProperty = new MetaAttributeMetadata('test');
        $metaPropertyName = 'test';
        $this->linkMetadata->expects(self::once())
            ->method('getMetaProperty')
            ->with($metaPropertyName)
            ->willReturn($metaProperty);

        self::assertSame($metaProperty, $this->decorator->getMetaProperty($metaPropertyName));
    }

    public function testAddMetaProperty(): void
    {
        $metaProperty = new MetaAttributeMetadata('test');
        $this->linkMetadata->expects(self::once())
            ->method('addMetaProperty')
            ->with(self::identicalTo($metaProperty))
            ->willReturn($metaProperty);

        self::assertSame($metaProperty, $this->decorator->addMetaProperty($metaProperty));
    }

    public function testRemoveMetaProperty(): void
    {
        $metaPropertyName = 'test';
        $this->linkMetadata->expects(self::once())
            ->method('removeMetaProperty')
            ->with($metaPropertyName);

        $this->decorator->removeMetaProperty($metaPropertyName);
    }
}
