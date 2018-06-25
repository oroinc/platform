<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass\ActionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ActionsPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActionsPass */
    protected $actionsPass;

    /** @var ContainerBuilder */
    protected $container;

    /** @var Definition */
    protected $actionFactory;

    /** @var Definition */
    protected $actionExtension;

    /** @var Definition */
    protected $massActionFactory;

    /** @var Definition */
    protected $iterableResultFactoryRegistry;

    protected function setUp()
    {
        $this->actionsPass = new ActionsPass();

        $this->actionFactory = new Definition();
        $this->actionExtension = new Definition();
        $this->massActionFactory = new Definition();
        $this->iterableResultFactoryRegistry = new Definition();

        $this->container = new ContainerBuilder();
        $this->container->setDefinition(ActionsPass::ACTION_FACTORY_SERVICE_ID, $this->actionFactory);
        $this->container->setDefinition(ActionsPass::ACTION_EXTENSION_SERVICE_ID, $this->actionExtension);
        $this->container->setDefinition(ActionsPass::MASS_ACTION_FACTORY_SERVICE_ID, $this->massActionFactory);
        $this->container->setDefinition(
            ActionsPass::ITERABLE_RESULT_FACTORY_REGISTRY_SERVICE_ID,
            $this->iterableResultFactoryRegistry
        );
    }

    /**
     * @param string $actionTagName
     * @param string $actionType
     *
     * @return Definition
     */
    protected function getActionDefinition($actionTagName, $actionType)
    {
        $action = new Definition();
        $action->setShared(false);
        $action->addTag($actionTagName, ['type' => $actionType]);

        return $action;
    }

    /**
     * @param int|null $priority
     *
     * @return Definition
     */
    protected function getActionProviderDefinition($priority = null)
    {
        $provider = new Definition();
        $attributes = [];
        if (null !== $priority) {
            $attributes['priority'] = $priority;
        }
        $provider->addTag(ActionsPass::ACTION_PROVIDER_TAG, $attributes);

        return $provider;
    }

    /**
     * @param int|null $priority
     *
     * @return Definition
     */
    protected function getIterableResultFactoryDefinition($priority = null)
    {
        $provider = new Definition();
        $attributes = [];
        if (null !== $priority) {
            $attributes['priority'] = $priority;
        }
        $provider->addTag(ActionsPass::ITERABLE_RESULT_FACTORY_TAG_NAME, $attributes);

        return $provider;
    }

    public function testProcessActions()
    {
        $this->container->setDefinition(
            'action1',
            $this->getActionDefinition(ActionsPass::ACTION_TAG_NAME, 'type1')
        );
        $this->container->setDefinition(
            'action2',
            $this->getActionDefinition(ActionsPass::ACTION_TAG_NAME, 'type2')
        );

        $this->actionsPass->process($this->container);

        self::assertEquals(
            [
                ['registerAction', ['type1', 'action1']],
                ['registerAction', ['type2', 'action2']],
            ],
            $this->actionFactory->getMethodCalls()
        );
    }

    public function testProcessActionsProviders()
    {
        $this->container->setDefinition('action_provider1', $this->getActionProviderDefinition());
        $this->container->setDefinition('action_provider2', $this->getActionProviderDefinition(-1));
        $this->container->setDefinition('action_provider3', $this->getActionProviderDefinition(1));

        $this->actionsPass->process($this->container);

        $providers = $this->actionExtension->getMethodCalls();
        self::assertEquals('addActionProvider', $providers[0][0]);
        self::assertEquals('action_provider2', $providers[0][1][1]);
        self::assertEquals('addActionProvider', $providers[1][0]);
        self::assertEquals('action_provider1', $providers[1][1][1]);
        self::assertEquals('addActionProvider', $providers[2][0]);
        self::assertEquals('action_provider3', $providers[2][1][1]);
    }

    public function testProcessIterableResultFactories()
    {
        $this->container->setDefinition('factory1', $this->getIterableResultFactoryDefinition());
        $this->container->setDefinition('factory2', $this->getIterableResultFactoryDefinition());

        $this->actionsPass->process($this->container);

        $factories = $this->iterableResultFactoryRegistry->getMethodCalls();
        self::assertEquals('addFactory', $factories[0][0]);
        self::assertEquals('factory1', $factories[0][1][1]);
        self::assertEquals('addFactory', $factories[1][0]);
        self::assertEquals('factory2', $factories[1][1][1]);
    }

    public function testProcessMassActions()
    {
        $this->container->setDefinition(
            'mass_action1',
            $this->getActionDefinition(ActionsPass::MASS_ACTION_TAG_NAME, 'type1')
        );
        $this->container->setDefinition(
            'mass_action2',
            $this->getActionDefinition(ActionsPass::MASS_ACTION_TAG_NAME, 'type2')
        );

        $this->actionsPass->process($this->container);

        self::assertEquals(
            [
                ['registerAction', ['type1', 'mass_action1']],
                ['registerAction', ['type2', 'mass_action2']],
            ],
            $this->massActionFactory->getMethodCalls()
        );
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\RuntimeException
     * @expectedExceptionMessage The service "action1" should be public.
     */
    public function testProcessPrivateAction()
    {
        $action = $this->getActionDefinition(ActionsPass::ACTION_TAG_NAME, 'type1');
        $action->setPublic(false);

        $this->container->setDefinition('action1', $action);

        $this->actionsPass->process($this->container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\RuntimeException
     * @expectedExceptionMessage The service "action1" should not be shared.
     */
    public function testProcessSharedAction()
    {
        $action = $this->getActionDefinition(ActionsPass::ACTION_TAG_NAME, 'type1');
        $action->setShared(true);

        $this->container->setDefinition('action1', $action);

        $this->actionsPass->process($this->container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\RuntimeException
     * @expectedExceptionMessage The service "mass_action1" should be public.
     */
    public function testProcessPrivateMassAction()
    {
        $action = $this->getActionDefinition(ActionsPass::MASS_ACTION_TAG_NAME, 'type1');
        $action->setPublic(false);

        $this->container->setDefinition('mass_action1', $action);

        $this->actionsPass->process($this->container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\RuntimeException
     * @expectedExceptionMessage The service "mass_action1" should not be shared.
     */
    public function testProcessSharedMassAction()
    {
        $action = $this->getActionDefinition(ActionsPass::MASS_ACTION_TAG_NAME, 'type1');
        $action->setShared(true);

        $this->container->setDefinition('mass_action1', $action);

        $this->actionsPass->process($this->container);
    }
}
