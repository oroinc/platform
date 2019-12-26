<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\NotificationHandlerPass;
use Oro\Bundle\NotificationBundle\Provider\NotificationManager;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class NotificationHandlerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var NotificationHandlerPass */
    private $compiler;

    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $manager;

    /** @var Definition */
    private $locator;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new NotificationHandlerPass();

        $this->manager = $this->container->setDefinition(
            'oro_notification.manager',
            new Definition(NotificationManager::class, [[], null])
        );
    }

    public function testProcessWhenNoHandlers()
    {
        $this->compiler->process($this->container);

        self::assertEquals([], $this->manager->getArgument(0));

        $serviceLocatorReference = $this->manager->getArgument(1);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals([], $serviceLocatorDef->getArgument(0));
    }

    public function testProcess()
    {
        $handler1 = $this->container->setDefinition('handler1', new Definition());
        $handler1->addTag('notification.handler');
        $handler2 = $this->container->setDefinition('handler2', new Definition());
        $handler2->addTag('notification.handler', ['priority' => -10]);
        $handler2->addTag('notification.handler', ['priority' => 10]);

        $this->compiler->process($this->container);

        self::assertEquals(['handler2', 'handler1', 'handler2'], $this->manager->getArgument(0));

        $serviceLocatorReference = $this->manager->getArgument(1);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'handler1' => new ServiceClosureArgument(new Reference('handler1')),
                'handler2' => new ServiceClosureArgument(new Reference('handler2')),
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
