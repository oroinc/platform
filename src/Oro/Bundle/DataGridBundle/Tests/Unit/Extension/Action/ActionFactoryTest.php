<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionFactory;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ActionFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    /** @var ActionFactory */
    protected $actionFactory;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->actionFactory = new ActionFactory($this->container);
    }

    /**
     * @expectedException \Oro\Bundle\DataGridBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "type" option must be defined. Action: action1.
     */
    public function testCreateActionWithoutType()
    {
        $this->actionFactory->createAction('action1', []);
    }

    /**
     * @expectedException \Oro\Bundle\DataGridBundle\Exception\RuntimeException
     * @expectedExceptionMessage Unknown action type "type1". Action: action1.
     */
    public function testCreateUnregisteredAction()
    {
        $this->actionFactory->createAction('action1', ['type' => 'type1']);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\DataGridBundle\Exception\RuntimeException
     * @expectedExceptionMessage An action should be an instance of "Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface", got "stdClass".
     */
    // @codingStandardsIgnoreEnd
    public function testCreateActionForInvalidActionClass()
    {
        $this->actionFactory->registerAction('type1', 'action_service.type1');

        $this->container->expects(self::once())
            ->method('get')
            ->with('action_service.type1')
            ->willReturn(new \stdClass());

        $this->actionFactory->createAction('action1', ['type' => 'type1']);
    }

    public function testCreateAction()
    {
        $action = $this->createMock(ActionInterface::class);
        $actionName = 'action1';
        $actionConfig = ['type' => 'type1'];

        $this->actionFactory->registerAction('type1', 'action_service.type1');

        $this->container->expects(self::once())
            ->method('get')
            ->with('action_service.type1')
            ->willReturn($action);

        $action->expects(self::once())
            ->method('setOptions')
            ->with(ActionConfiguration::createNamed($actionName, $actionConfig));

        self::assertSame(
            $action,
            $this->actionFactory->createAction($actionName, $actionConfig)
        );
    }
}
