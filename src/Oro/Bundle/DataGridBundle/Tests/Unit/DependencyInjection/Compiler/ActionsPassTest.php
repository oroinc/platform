<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\DataGridBundle\DependencyInjection\Compiler\ActionsPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ActionsPassTest extends \PHPUnit\Framework\TestCase
{
    private const FACTORY_SERVICE_ID = 'oro_datagrid.extension.action.factory';
    private const TAG_NAME           = 'oro_datagrid.extension.action.type';

    /** @var ActionsPass */
    private $actionsPass;

    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $actionFactory;

    /** @var Definition */
    private $iterableResultFactoryRegistry;

    protected function setUp(): void
    {
        $this->actionsPass = new ActionsPass(self::FACTORY_SERVICE_ID, self::TAG_NAME);

        $this->actionFactory = new Definition();
        $this->iterableResultFactoryRegistry = new Definition();

        $this->container = new ContainerBuilder();
        $this->container->setDefinition(self::FACTORY_SERVICE_ID, $this->actionFactory);
    }

    public function testProcessActions()
    {
        $this->container->register('action1')
            ->setShared(false)
            ->addTag(self::TAG_NAME, ['type' => 'type1']);
        $this->container->register('action2')
            ->setShared(false)
            ->addTag(self::TAG_NAME, ['type' => 'type2']);

        $this->actionsPass->process($this->container);

        $serviceLocatorReference = $this->actionFactory->getArgument(0);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'type1' => new ServiceClosureArgument(new Reference('action1')),
                'type2' => new ServiceClosureArgument(new Reference('action2'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }

    public function testProcessSharedAction()
    {
        $this->expectException(\Symfony\Component\DependencyInjection\Exception\RuntimeException::class);
        $this->expectExceptionMessage('The service "action1" should not be shared.');

        $this->container->register('action1')
            ->setShared(true)
            ->addTag(self::TAG_NAME, ['type' => 'type1']);

        $this->actionsPass->process($this->container);
    }
}
