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
    /**
     * @param array $actions
     *
     * @return MassActionFactory
     */
    private function getActionFactory(array $actions)
    {
        $containerBuilder = TestContainerBuilder::create();
        foreach ($actions as $type => $action) {
            $containerBuilder->add($type, $action);
        }

        return new MassActionFactory($containerBuilder->getContainer($this));
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

    public function testCreateActionWhenRegularActionIsCreatedInsteadOfMassAction()
    {
        $action = $this->createMock(ActionInterface::class);
        $factory = $this->getActionFactory(['type1' => $action]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                'An action should be an instance of "%s", got "%s".',
                MassActionInterface::class,
                get_class($action)
            )
        );

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
