<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionFactory;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class ActionFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array $actions
     *
     * @return ActionFactory
     */
    private function getActionFactory(array $actions)
    {
        $containerBuilder = TestContainerBuilder::create();
        foreach ($actions as $type => $action) {
            $containerBuilder->add($type, $action);
        }

        return new ActionFactory($containerBuilder->getContainer($this));
    }

    /**
     * @expectedException \Oro\Bundle\DataGridBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "type" option must be defined. Action: action1.
     */
    public function testCreateActionWithoutType()
    {
        $factory = $this->getActionFactory([]);
        $factory->createAction('action1', []);
    }

    /**
     * @expectedException \Oro\Bundle\DataGridBundle\Exception\RuntimeException
     * @expectedExceptionMessage Unknown action type "type1". Action: action1.
     */
    public function testCreateUnregisteredAction()
    {
        $factory = $this->getActionFactory([]);
        $factory->createAction('action1', ['type' => 'type1']);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\DataGridBundle\Exception\RuntimeException
     * @expectedExceptionMessage An action should be an instance of "Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface", got "stdClass".
     */
    // @codingStandardsIgnoreEnd
    public function testCreateActionForInvalidActionClass()
    {
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
