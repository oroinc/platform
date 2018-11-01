<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectSubresources;

use Oro\Bundle\ApiBundle\Processor\CollectSubresources\CollectSubresourcesContext;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresourcesCollection;

class CollectSubresourcesContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var CollectSubresourcesContext */
    private $context;

    protected function setUp()
    {
        $this->context = new CollectSubresourcesContext();
    }

    public function testResultShouldBeInitialized()
    {
        self::assertInstanceOf(ApiResourceSubresourcesCollection::class, $this->context->getResult());
    }

    public function testResources()
    {
        self::assertEquals([], $this->context->getResources());
        self::assertFalse($this->context->hasResource('Test\Class'));
        self::assertNull($this->context->getResource('Test\Class'));

        $resource = new ApiResource('Test\Class');
        $this->context->setResources([$resource]);
        self::assertEquals(['Test\Class' => $resource], $this->context->getResources());
        self::assertTrue($this->context->hasResource('Test\Class'));
        self::assertSame($resource, $this->context->getResource('Test\Class'));
    }

    public function testAccessibleResources()
    {
        self::assertEquals([], $this->context->getAccessibleResources());

        $this->context->setAccessibleResources(['Test\Class']);
        self::assertEquals(['Test\Class'], $this->context->getAccessibleResources());
    }
}
