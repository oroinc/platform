<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectSubresources;

use Oro\Bundle\ApiBundle\Processor\CollectSubresources\CollectSubresourcesContext;
use Oro\Bundle\ApiBundle\Request\ApiResource;

class CollectSubresourcesContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    /** @var CollectSubresourcesContext */
    protected $context;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = new CollectSubresourcesContext($this->configProvider, $this->metadataProvider);
    }

    public function testResultShouldBeInitialized()
    {
        $this->assertInstanceOf(
            'Oro\Bundle\ApiBundle\Request\ApiResourceSubresourcesCollection',
            $this->context->getResult()
        );
    }

    public function testResources()
    {
        $this->assertEquals([], $this->context->getResources());
        $this->assertFalse($this->context->hasResource('Test\Class'));
        $this->assertNull($this->context->getResource('Test\Class'));

        $resource = new ApiResource('Test\Class');
        $this->context->setResources([$resource]);
        $this->assertEquals(['Test\Class' => $resource], $this->context->getResources());
        $this->assertTrue($this->context->hasResource('Test\Class'));
        $this->assertSame($resource, $this->context->getResource('Test\Class'));
    }
}
