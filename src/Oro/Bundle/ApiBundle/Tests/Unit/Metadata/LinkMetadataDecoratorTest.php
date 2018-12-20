<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\LinkMetadataDecorator;
use Oro\Bundle\ApiBundle\Metadata\LinkMetadataInterface;
use Oro\Bundle\ApiBundle\Metadata\MetaAttributeMetadata;

class LinkMetadataDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|LinkMetadataInterface */
    private $linkMetadata;

    /** @var LinkMetadataDecorator */
    private $decorator;

    protected function setUp()
    {
        $this->linkMetadata = $this->createMock(LinkMetadataInterface::class);
        $this->decorator = new LinkMetadataDecoratorStub($this->linkMetadata);
    }

    public function testClone()
    {
        $decoratorClone = clone $this->decorator;

        self::assertEquals($this->decorator, $decoratorClone);
        self::assertAttributeSame($this->linkMetadata, 'link', $decoratorClone);
    }

    public function testToArray()
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

    public function testGetHref()
    {
        $href = 'http://test.com/api/resource';
        $dataAccessor = $this->createMock(DataAccessorInterface::class);
        $this->linkMetadata->expects(self::once())
            ->method('getHref')
            ->with(self::identicalTo($dataAccessor))
            ->willReturn($href);

        self::assertEquals($href, $this->decorator->getHref($dataAccessor));
    }

    public function testMetaProperties()
    {
        $metaProperties = ['test' => new MetaAttributeMetadata('test')];
        $this->linkMetadata->expects(self::once())
            ->method('getMetaProperties')
            ->willReturn($metaProperties);

        self::assertEquals($metaProperties, $this->decorator->getMetaProperties());
    }

    public function testHasMetaProperty()
    {
        $result = true;
        $metaPropertyName = 'test';
        $this->linkMetadata->expects(self::once())
            ->method('hasMetaProperty')
            ->with($metaPropertyName)
            ->willReturn($result);

        self::assertSame($result, $this->decorator->hasMetaProperty($metaPropertyName));
    }

    public function testGetMetaProperty()
    {
        $metaProperty = new MetaAttributeMetadata('test');
        $metaPropertyName = 'test';
        $this->linkMetadata->expects(self::once())
            ->method('getMetaProperty')
            ->with($metaPropertyName)
            ->willReturn($metaProperty);

        self::assertSame($metaProperty, $this->decorator->getMetaProperty($metaPropertyName));
    }

    public function testAddMetaProperty()
    {
        $metaProperty = new MetaAttributeMetadata('test');
        $this->linkMetadata->expects(self::once())
            ->method('addMetaProperty')
            ->with(self::identicalTo($metaProperty))
            ->willReturn($metaProperty);

        self::assertSame($metaProperty, $this->decorator->addMetaProperty($metaProperty));
    }

    public function testRemoveMetaProperty()
    {
        $metaPropertyName = 'test';
        $this->linkMetadata->expects(self::once())
            ->method('removeMetaProperty')
            ->with($metaPropertyName);

        $this->decorator->removeMetaProperty($metaPropertyName);
    }
}
