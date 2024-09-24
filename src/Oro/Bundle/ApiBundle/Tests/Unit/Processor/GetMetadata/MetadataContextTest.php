<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\Extra\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestMetadataExtra;

class MetadataContextTest extends \PHPUnit\Framework\TestCase
{
    private MetadataContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new MetadataContext();
    }

    public function testInitialize()
    {
        self::assertTrue($this->context->has('requestType'));

        self::assertTrue($this->context->has('extra'));
        self::assertEquals([], $this->context->get('extra'));
    }

    public function testClassName()
    {
        $this->context->setClassName('test');
        self::assertEquals('test', $this->context->getClassName());
        self::assertEquals('test', $this->context->get('class'));
    }

    public function testTargetAction()
    {
        self::assertNull($this->context->getTargetAction());
        self::assertFalse($this->context->has('targetAction'));

        $this->context->setTargetAction('test');
        self::assertEquals('test', $this->context->getTargetAction());
        self::assertTrue($this->context->has('targetAction'));
        self::assertEquals('test', $this->context->get('targetAction'));

        $this->context->setTargetAction(null);
        self::assertNull($this->context->getTargetAction());
        self::assertFalse($this->context->has('targetAction'));
    }

    public function testParentAction()
    {
        self::assertNull($this->context->getParentAction());
        self::assertTrue($this->context->has('parentAction'));
        self::assertSame('', $this->context->get('parentAction'));

        $this->context->setParentAction('test');
        self::assertEquals('test', $this->context->getParentAction());
        self::assertTrue($this->context->has('parentAction'));
        self::assertEquals('test', $this->context->get('parentAction'));

        $this->context->setParentAction(null);
        self::assertNull($this->context->getParentAction());
        self::assertTrue($this->context->has('parentAction'));
        self::assertSame('', $this->context->get('parentAction'));
    }

    public function testConfig()
    {
        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        self::assertSame($config, $this->context->getConfig());
    }

    public function testExtras()
    {
        self::assertSame([], $this->context->getExtras());
        self::assertFalse($this->context->hasExtra('test'));

        $extras = [new TestMetadataExtra('test')];
        $this->context->setExtras($extras);
        self::assertEquals($extras, $this->context->getExtras());
        self::assertTrue($this->context->hasExtra('test'));
        self::assertFalse($this->context->hasExtra('another'));

        $this->context->setExtras([]);
        self::assertSame([], $this->context->getExtras());
        self::assertFalse($this->context->hasExtra('test'));
    }

    public function testSetExtras()
    {
        $extra = $this->createMock(MetadataExtraInterface::class);
        $extra->expects(self::once())
            ->method('getName')
            ->willReturn('test');
        $extra->expects(self::once())
            ->method('configureContext')
            ->with(self::identicalTo($this->context));

        $this->context->setExtras([$extra]);
        self::assertEquals(['test'], $this->context->get('extra'));
    }

    public function testSetInvalidExtras()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected an array of "Oro\Bundle\ApiBundle\Metadata\Extra\MetadataExtraInterface".'
        );

        $this->context->setExtras([new \stdClass()]);
    }

    public function testWithExcludedProperties()
    {
        self::assertFalse($this->context->getWithExcludedProperties());

        $this->context->setWithExcludedProperties(true);
        self::assertTrue($this->context->getWithExcludedProperties());
        self::assertTrue($this->context->get('withExcludedProperties'));
    }
}
