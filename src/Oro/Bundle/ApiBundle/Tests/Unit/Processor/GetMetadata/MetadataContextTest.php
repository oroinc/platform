<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;

class MetadataContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var MetadataContext */
    protected $context;

    protected function setUp()
    {
        $this->context = new MetadataContext();
    }

    public function testInitialize()
    {
        $this->assertTrue($this->context->has(MetadataContext::REQUEST_TYPE));

        $this->assertTrue($this->context->has(MetadataContext::EXTRA));
        $this->assertEquals([], $this->context->get(MetadataContext::EXTRA));
    }

    public function testClassName()
    {
        $this->assertNull($this->context->getClassName());

        $this->context->setClassName('test');
        $this->assertEquals('test', $this->context->getClassName());
        $this->assertEquals('test', $this->context->get(MetadataContext::CLASS_NAME));
    }

    public function testConfig()
    {
        $this->assertNull($this->context->getConfig());

        $config = new EntityDefinitionConfig();
        $this->context->setConfig($config);
        $this->assertSame($config, $this->context->getConfig());

        $this->context->setConfig(null);
        $this->assertNull($this->context->getConfig());
    }

    public function testHasExtraAndGetExtras()
    {
        $extra = $this->getMock('Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface');
        $extra->expects($this->any())
            ->method('getName')
            ->willReturn('test');

        $this->context->setExtras([$extra]);
        $this->assertEquals([$extra], $this->context->getExtras());

        $this->assertTrue($this->context->hasExtra('test'));
        $this->assertFalse($this->context->hasExtra('another'));
    }

    public function testSetExtras()
    {
        $extra = $this->getMock('Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface');
        $extra->expects($this->once())
            ->method('getName')
            ->willReturn('test');
        $extra->expects($this->once())
            ->method('configureContext')
            ->with($this->identicalTo($this->context));

        $this->context->setExtras([$extra]);
        $this->assertEquals(['test'], $this->context->get(MetadataContext::EXTRA));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected an array of "Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface".
     */
    public function testSetInvalidExtras()
    {
        $this->context->setExtras([new \stdClass()]);
    }
}
