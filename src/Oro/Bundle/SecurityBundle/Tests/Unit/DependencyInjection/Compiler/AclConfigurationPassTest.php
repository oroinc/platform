<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AclConfigurationPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class AclConfigurationPassTest extends \PHPUnit\Framework\TestCase
{
    private ContainerBuilder $container;

    private Definition $extensionSelector;

    private AclConfigurationPass $compiler;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->extensionSelector = $this->container->register('oro_security.acl.extension_selector');
        $this->container->register('security.acl.dbal.provider');
        $this->container->register('security.acl.voter.basic_permissions')
            ->addArgument([]);
        $this->container->register('oro_security.acl.provider');
        $this->container->register('security.acl.cache.doctrine');

        $this->compiler = new AclConfigurationPass();
    }

    public function testProcessWhenNoTaggedServices(): void
    {
        $this->compiler->process($this->container);

        self::assertEquals([], $this->extensionSelector->getArgument('$extensionNames'));

        $serviceLocatorReference = $this->extensionSelector->getArgument('$extensionContainer');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals([], $serviceLocatorDef->getArgument(0));
    }

    public function testProcess(): void
    {
        $this->container->setDefinition('extension_1', new Definition())
            ->addTag('oro_security.acl.extension');
        $this->container->setDefinition('extension_2', new Definition())
            ->addTag('oro_security.acl.extension', ['priority' => -10]);
        $this->container->setDefinition('extension_3', new Definition())
            ->addTag('oro_security.acl.extension', ['priority' => 10]);

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                'extension_2',
                'extension_1',
                'extension_3'
            ],
            $this->extensionSelector->getArgument('$extensionNames')
        );

        $serviceLocatorReference = $this->extensionSelector->getArgument('$extensionContainer');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'extension_2' => new ServiceClosureArgument(new Reference('extension_2')),
                'extension_1' => new ServiceClosureArgument(new Reference('extension_1')),
                'extension_3' => new ServiceClosureArgument(new Reference('extension_3')),
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
