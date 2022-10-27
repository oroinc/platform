<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionFactory;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class MassActionFactoryTest extends \PHPUnit\Framework\TestCase
{
    private function getActionFactory(array $actions): MassActionFactory
    {
        $containerBuilder = TestContainerBuilder::create();
        foreach ($actions as $type => $action) {
            $containerBuilder->add($type, $action);
        }

        return new MassActionFactory($containerBuilder->getContainer($this));
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

    public function testCreateActionWhenRegularActionIsCreatedInsteadOfMassAction()
    {
        $action = $this->createMock(ActionInterface::class);
        $factory = $this->getActionFactory(['type1' => $action]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'An action should be an instance of "%s", got "%s".',
            MassActionInterface::class,
            get_class($action)
        ));

        $factory->createAction('action1', ['type' => 'type1']);
    }

    public function testCreateAction()
    {
        $action = $this->createMock(MassActionInterface::class);
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
