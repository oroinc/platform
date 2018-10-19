<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MassActionFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    /** @var MassActionFactory */
    protected $massActionFactory;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->massActionFactory = new MassActionFactory($this->container);
    }

    /**
     * @expectedException \Oro\Bundle\DataGridBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "type" option must be defined. Action: action1.
     */
    public function testCreateActionWithoutType()
    {
        $this->massActionFactory->createAction('action1', []);
    }

    /**
     * @expectedException \Oro\Bundle\DataGridBundle\Exception\RuntimeException
     * @expectedExceptionMessage Unknown action type "type1". Action: action1.
     */
    public function testCreateUnregisteredAction()
    {
        $this->massActionFactory->createAction('action1', ['type' => 'type1']);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\DataGridBundle\Exception\RuntimeException
     * @expectedExceptionMessage An action should be an instance of "Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface", got "stdClass".
     */
    // @codingStandardsIgnoreEnd
    public function testCreateActionForInvalidActionClass()
    {
        $this->massActionFactory->registerAction('type1', 'mass_action_service.type1');

        $this->container->expects(self::once())
            ->method('get')
            ->with('mass_action_service.type1')
            ->willReturn(new \stdClass());

        $this->massActionFactory->createAction('action1', ['type' => 'type1']);
    }

    public function testCreateActionWhenRegularActionIsCreatedInsteadOfMassAction()
    {
        $action = $this->createMock(ActionInterface::class);
        $this->massActionFactory->registerAction('type1', 'mass_action_service.type1');

        $this->container->expects(self::once())
            ->method('get')
            ->with('mass_action_service.type1')
            ->willReturn($action);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                'An action should be an instance of "%s", got "%s".',
                MassActionInterface::class,
                get_class($action)
            )
        );

        $this->massActionFactory->createAction('action1', ['type' => 'type1']);
    }

    public function testCreateAction()
    {
        $action = $this->createMock(MassActionInterface::class);
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1'];

        $this->massActionFactory->registerAction('type1', 'mass_action_service.type1');

        $this->container->expects(self::once())
            ->method('get')
            ->with('mass_action_service.type1')
            ->willReturn($action);

        $action->expects(self::once())
            ->method('setOptions')
            ->with(ActionConfiguration::createNamed($actionName, $actionConfig));

        self::assertSame(
            $action,
            $this->massActionFactory->createAction($actionName, $actionConfig)
        );
    }
}
