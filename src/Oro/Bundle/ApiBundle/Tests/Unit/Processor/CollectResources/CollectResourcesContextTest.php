<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Request\ApiResourceCollection;

class CollectResourcesContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var CollectResourcesContext */
    private $context;

    protected function setUp()
    {
        $this->context = new CollectResourcesContext();
    }

    public function testResultShouldBeInitialized()
    {
        self::assertInstanceOf(ApiResourceCollection::class, $this->context->getResult());
    }

    public function testAccessibleResources()
    {
        self::assertEquals([], $this->context->getAccessibleResources());

        $this->context->setAccessibleResources(['Test\Class']);
        self::assertEquals(['Test\Class'], $this->context->getAccessibleResources());
    }
}
