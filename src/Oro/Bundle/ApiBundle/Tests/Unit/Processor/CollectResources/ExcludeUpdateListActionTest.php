<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Processor\CollectResources\AddExcludedActions;
use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResources\ExcludeUpdateListAction;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use PHPUnit\Framework\TestCase;

class ExcludeUpdateListActionTest extends TestCase
{
    public function testProcessOnEmptyExcludedActions(): void
    {
        $resource = new ApiResource('Test\Entity1');
        $context = new CollectResourcesContext();
        $context->getResult()->add($resource);

        $processor = new ExcludeUpdateListAction();
        $processor->process($context);

        self::assertEquals(
            [ApiAction::UPDATE_LIST],
            $resource->getExcludedActions()
        );
    }

    public function testProcessWithManuallyEnabledUpdateListAction(): void
    {
        $resource = new ApiResource('Test\Entity1');
        $context = new CollectResourcesContext();
        $context->getResult()->add($resource);

        $actionConfig = new ActionsConfig();
        $actionConfig->addAction(ApiAction::UPDATE_LIST)->setExcluded(false);
        $context->set(AddExcludedActions::ACTIONS_CONFIG_KEY, ['Test\Entity1' => $actionConfig]);

        $processor = new ExcludeUpdateListAction();
        $processor->process($context);

        self::assertCount(0, $resource->getExcludedActions());
    }

    public function testProcessWithExcludedGetAction(): void
    {
        $resource = new ApiResource('Test\Entity1');
        $resource->addExcludedAction(ApiAction::GET);
        $context = new CollectResourcesContext();
        $context->getResult()->add($resource);

        $processor = new ExcludeUpdateListAction();
        $processor->process($context);

        self::assertEquals(
            [ApiAction::GET, ApiAction::UPDATE_LIST],
            $resource->getExcludedActions()
        );
    }

    public function testProcessWithExcludedGetActionAndManuallyEnabledUpdateListAction(): void
    {
        $resource = new ApiResource('Test\Entity1');
        $resource->addExcludedAction(ApiAction::GET);
        $context = new CollectResourcesContext();
        $context->getResult()->add($resource);

        $actionConfig = new ActionsConfig();
        $actionConfig->addAction(ApiAction::UPDATE_LIST)->setExcluded(false);
        $context->set(AddExcludedActions::ACTIONS_CONFIG_KEY, ['Test\Entity1' => $actionConfig]);

        $processor = new ExcludeUpdateListAction();
        $processor->process($context);

        self::assertEquals(
            [ApiAction::GET],
            $resource->getExcludedActions()
        );
    }

    public function testProcessWithExcludedGetAndCreateActions(): void
    {
        $resource = new ApiResource('Test\Entity1');
        $resource->addExcludedAction(ApiAction::GET);
        $resource->addExcludedAction(ApiAction::CREATE);
        $context = new CollectResourcesContext();
        $context->getResult()->add($resource);

        $processor = new ExcludeUpdateListAction();
        $processor->process($context);

        self::assertEquals(
            [ApiAction::GET, ApiAction::CREATE, ApiAction::UPDATE_LIST],
            $resource->getExcludedActions()
        );
    }

    public function testProcessWithExcludedGetAndCreateActionsAndManuallyEnabledUpdateListAction(): void
    {
        $resource = new ApiResource('Test\Entity1');
        $resource->addExcludedAction(ApiAction::GET);
        $resource->addExcludedAction(ApiAction::CREATE);
        $context = new CollectResourcesContext();
        $context->getResult()->add($resource);

        $actionConfig = new ActionsConfig();
        $actionConfig->addAction(ApiAction::UPDATE_LIST)->setExcluded(false);
        $context->set(AddExcludedActions::ACTIONS_CONFIG_KEY, ['Test\Entity1' => $actionConfig]);

        $processor = new ExcludeUpdateListAction();
        $processor->process($context);

        self::assertEquals(
            [ApiAction::GET, ApiAction::CREATE],
            $resource->getExcludedActions()
        );
    }

    public function testProcessWithExcludedGetAndCreateAndUpdateActions(): void
    {
        $resource = new ApiResource('Test\Entity1');
        $resource->addExcludedAction(ApiAction::GET);
        $resource->addExcludedAction(ApiAction::CREATE);
        $resource->addExcludedAction(ApiAction::UPDATE);
        $context = new CollectResourcesContext();
        $context->getResult()->add($resource);

        $processor = new ExcludeUpdateListAction();
        $processor->process($context);

        self::assertEquals(
            [ApiAction::GET, ApiAction::CREATE, ApiAction::UPDATE, ApiAction::UPDATE_LIST],
            $resource->getExcludedActions()
        );
    }

    public function testProcessWithExcludedGetAndCreateAndUpdateActionsAndManuallyEnabledUpdateListAction(): void
    {
        $resource = new ApiResource('Test\Entity1');
        $resource->addExcludedAction(ApiAction::GET);
        $resource->addExcludedAction(ApiAction::CREATE);
        $resource->addExcludedAction(ApiAction::UPDATE);
        $context = new CollectResourcesContext();
        $context->getResult()->add($resource);

        $actionConfig = new ActionsConfig();
        $actionConfig->addAction(ApiAction::UPDATE_LIST)->setExcluded(false);
        $context->set(AddExcludedActions::ACTIONS_CONFIG_KEY, ['Test\Entity1' => $actionConfig]);

        $processor = new ExcludeUpdateListAction();
        $processor->process($context);

        self::assertEquals(
            [ApiAction::GET, ApiAction::CREATE, ApiAction::UPDATE, ApiAction::UPDATE_LIST],
            $resource->getExcludedActions()
        );
    }
}
