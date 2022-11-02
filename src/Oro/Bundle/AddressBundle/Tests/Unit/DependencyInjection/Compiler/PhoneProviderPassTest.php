<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\AddressBundle\DependencyInjection\Compiler\PhoneProviderPass;
use Oro\Bundle\AddressBundle\Provider\PhoneProvider;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class PhoneProviderPassTest extends \PHPUnit\Framework\TestCase
{
    private ContainerBuilder $container;

    private Definition $phoneProvider;

    private PhoneProviderPass $compiler;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->phoneProvider = $this->container->setDefinition(
            'oro_address.provider.phone',
            new Definition(PhoneProvider::class, [[], null])
        );

        $this->compiler = new PhoneProviderPass();
    }

    public function testProcessWhenNoProviders(): void
    {
        $this->compiler->process($this->container);

        self::assertEquals([], $this->phoneProvider->getArgument('$phoneProviderMap'));

        $serviceLocatorReference = $this->phoneProvider->getArgument('$phoneProviderContainer');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals([], $serviceLocatorDef->getArgument(0));
    }

    public function testProcessNoClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The attribute "class" is required for "oro_address.phone_provider" tag. Service: "provider1".'
        );

        $this->container->register('provider1')
            ->addTag('oro_address.phone_provider');

        $this->compiler->process($this->container);
    }

    public function testProcess(): void
    {
        $this->container->register('provider1')
            ->addTag('oro_address.phone_provider', ['class' => 'Test\Class1']);
        $this->container->register('provider2')
            ->addTag('oro_address.phone_provider', ['class' => 'Test\Class2']);
        $this->container->register('provider3')
            ->addTag('oro_address.phone_provider', ['class' => 'Test\Class1', 'priority' => 100]);
        $this->container->register('provider4')
            ->addTag('oro_address.phone_provider', ['class' => 'Test\Class1', 'priority' => -100]);

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                'Test\Class1' => ['provider4', 'provider1', 'provider3'],
                'Test\Class2' => ['provider2']
            ],
            $this->phoneProvider->getArgument('$phoneProviderMap')
        );

        $serviceLocatorReference = $this->phoneProvider->getArgument('$phoneProviderContainer');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'provider1' => new ServiceClosureArgument(new Reference('provider1')),
                'provider2' => new ServiceClosureArgument(new Reference('provider2')),
                'provider3' => new ServiceClosureArgument(new Reference('provider3')),
                'provider4' => new ServiceClosureArgument(new Reference('provider4'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
