<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResources\LoadAccessibleResources;
use Oro\Bundle\ApiBundle\Request\ApiResource;

class LoadAccessibleResourcesTest extends \PHPUnit_Framework_TestCase
{
    /** @var LoadAccessibleResources */
    private $processor;

    protected function setUp()
    {
        $this->processor = new LoadAccessibleResources();
    }

    public function testProcessWhenAccessibleResourcesAreAlreadyBuilt()
    {
        $context = new CollectResourcesContext();
        $context->setAccessibleResources(['Test\Entity']);

        $this->processor->process($context);

        self::assertEquals(['Test\Entity'], $context->getAccessibleResources());
    }

    public function testProcessAccessibleResource()
    {
        $context = new CollectResourcesContext();

        $resources = $context->getResult();
        $resources->add(new ApiResource('Test\Entity1'));
        $resources->add(new ApiResource('Test\Entity2'));
        $resources->get('Test\Entity2')->setExcludedActions(['get']);
        $resources->add(new ApiResource('Test\Entity3'));
        $resources->get('Test\Entity3')->setExcludedActions(['get_list']);

        $this->processor->process($context);

        $this->assertEquals(
            ['Test\Entity1', 'Test\Entity3'],
            $context->getAccessibleResources()
        );
    }
}
