<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;

class CollectResourcesContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var CollectResourcesContext */
    protected $context;

    protected function setUp()
    {
        $this->context = new CollectResourcesContext();
    }

    public function testResultShouldBeInitialized()
    {
        $this->assertInstanceOf(
            'Oro\Bundle\ApiBundle\Request\ApiResourceCollection',
            $this->context->getResult()
        );
    }

    public function testAccessibleResources()
    {
        $this->assertEquals([], $this->context->getAccessibleResources());

        $this->context->setAccessibleResources(['Test\Class']);
        $this->assertEquals(['Test\Class'], $this->context->getAccessibleResources());
    }
}
