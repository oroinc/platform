<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Action;

use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionFactory;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class ActionFactoryTest extends \PHPUnit\Framework\TestCase
{
    private function getActionFactory(array $actions): ActionFactory
    {
        $containerBuilder = TestContainerBuilder::create();
        foreach ($actions as $type => $action) {
            $containerBuilder->add($type, $action);
        }

        return new ActionFactory($containerBuilder->getContainer($this));
    }

    public function testCreateActionWithoutType()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The "type" option must be defined. Action: action1.');

        $factory = $this->getActionFactory([]);
        $factory->createAction('action1', []);
    }

    public function testCreateUnregisteredAction()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unknown action type "type1". Action: action1.');

        $factory = $this->getActionFactory([]);
        $factory->createAction('action1', ['type' => 'type1']);
    }

    public function testCreateActionForInvalidActionClass()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'An action should be an instance of "%s", got "stdClass".',
            ActionInterface::class
        ));

        $factory = $this->getActionFactory(['type1' => new \stdClass()]);
        $factory->createAction('action1', ['type' => 'type1']);
    }

    public function testCreateAction()
    {
        $action = $this->createMock(ActionInterface::class);
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1'];

        $factory = $this->getActionFactory(['type1' => $action]);

        $action->expects(self::once())
            ->method('setOptions')
            ->with(ActionConfiguration::createNamed($actionName, $actionConfig));

        self::assertSame(
            $action,
            $factory->createAction($actionName, $actionConfig)
        );
    }
}
