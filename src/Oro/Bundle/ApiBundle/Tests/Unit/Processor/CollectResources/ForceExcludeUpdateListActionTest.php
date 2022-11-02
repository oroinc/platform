<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResources\ForceExcludeUpdateListAction;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiResource;

class ForceExcludeUpdateListActionTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessOnEmptyExcludedActions()
    {
        $resource = new ApiResource('Test\Entity1');
        $context = new CollectResourcesContext();
        $context->getResult()->add($resource);

        $processor = new ForceExcludeUpdateListAction();
        $processor->process($context);

        self::assertEquals(
            [ApiAction::UPDATE_LIST],
            $resource->getExcludedActions()
        );
    }

    public function testProcessWithExcludedUpdateListAction()
    {
        $resource = new ApiResource('Test\Entity1');
        $resource->addExcludedAction(ApiAction::UPDATE_LIST);
        $context = new CollectResourcesContext();
        $context->getResult()->add($resource);

        $processor = new ForceExcludeUpdateListAction();
        $processor->process($context);

        self::assertEquals(
            [ApiAction::UPDATE_LIST],
            $resource->getExcludedActions()
        );
    }

    public function testProcessWithExcludedGetActionAndManuallyEnabledUpdateListAction()
    {
        $resource = new ApiResource('Test\Entity1');
        $resource->addExcludedAction(ApiAction::GET);
        $context = new CollectResourcesContext();
        $context->getResult()->add($resource);

        $processor = new ForceExcludeUpdateListAction();
        $processor->process($context);

        self::assertEquals(
            [ApiAction::GET, ApiAction::UPDATE_LIST],
            $resource->getExcludedActions()
        );
    }
}
