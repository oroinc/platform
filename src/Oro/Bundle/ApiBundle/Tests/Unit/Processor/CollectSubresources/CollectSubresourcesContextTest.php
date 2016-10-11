<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectSubresources;

use Oro\Bundle\ApiBundle\Processor\CollectSubresources\CollectSubresourcesContext;
use Oro\Bundle\ApiBundle\Request\ApiResource;

class CollectSubresourcesContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var CollectSubresourcesContext */
    protected $context;

    protected function setUp()
    {
        $this->context = new CollectSubresourcesContext();
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

    public function testAccessibleResources()
    {
        $this->assertEquals([], $this->context->getAccessibleResources());

        $this->context->setAccessibleResources(['Test\Class']);
        $this->assertEquals(['Test\Class'], $this->context->getAccessibleResources());
    }
}
