<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestMetadataExtra;

class MetadataContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var MetadataContext */
    private $context;

    protected function setUp()
    {
        $this->context = new MetadataContext();
    }

    public function testInitialize()
    {
        self::assertTrue($this->context->has(MetadataContext::REQUEST_TYPE));

        self::assertTrue($this->context->has(MetadataContext::EXTRA));
        self::assertEquals([], $this->context->get(MetadataContext::EXTRA));
    }

    public function testClassName()
    {
        self::assertNull($this->context->getClassName());

        $this->context->setClassName('test');
        self::assertEquals('test', $this->context->getClassName());
        self::assertEquals('test', $this->context->get(MetadataContext::CLASS_NAME));
    }

    public function testTargetAction()
    {
        self::assertNull($this->context->getTargetAction());

        $this->context->setTargetAction('test');
        self::assertEquals('test', $this->context->getTargetAction());
        self::assertEquals('test', $this->context->get(MetadataContext::TARGET_ACTION));
    }

    public function testConfig()
    {
        self::assertNull($this->context->getConfig());

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
        self::assertEquals(['test'], $this->context->get(MetadataContext::EXTRA));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected an array of "Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface".
     */
    public function testSetInvalidExtras()
    {
        $this->context->setExtras([new \stdClass()]);
    }

    public function testWithExcludedProperties()
    {
        self::assertFalse($this->context->getWithExcludedProperties());

        $this->context->setWithExcludedProperties(true);
        self::assertTrue($this->context->getWithExcludedProperties());
        self::assertTrue($this->context->get(MetadataContext::WITH_EXCLUDED_PROPERTIES));
    }
}
