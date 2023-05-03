<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\EmailTemplateVariablesPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class EmailTemplateVariablesPassTest extends \PHPUnit\Framework\TestCase
{
    private const CHAIN_PROVIDER_SERVICE = 'oro_email.emailtemplate.variable_provider';
    private const PROVIDER_TAG           = 'oro_email.emailtemplate.variable_provider';

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $chainProvider = $container->register(self::CHAIN_PROVIDER_SERVICE)
            ->setArguments([null, [], []]);
        $container->register('system_provider1')
            ->addTag(self::PROVIDER_TAG, ['scope' => 'system']);
        $container->register('system_provider2')
            ->addTag(self::PROVIDER_TAG, ['scope' => 'system', 'priority' => 100]);
        $container->register('system_provider3')
            ->addTag(self::PROVIDER_TAG, ['scope' => 'system', 'priority' => -100]);
        $container->register('entity_provider1')
            ->addTag(self::PROVIDER_TAG, ['scope' => 'entity']);
        $container->register('entity_provider2')
            ->addTag(self::PROVIDER_TAG, ['scope' => 'entity', 'priority' => 100]);
        $container->register('entity_provider3')
            ->addTag(self::PROVIDER_TAG, ['scope' => 'entity', 'priority' => -100]);

        $compiler = new EmailTemplateVariablesPass();
        $compiler->process($container);

        self::assertInstanceOf(Reference::class, $chainProvider->getArgument(0));
        $providers = $container->getDefinition((string)$chainProvider->getArgument(0));
        self::assertEquals(ServiceLocator::class, $providers->getClass());
        $this->assertEquals(
            [
                'system_provider1' => new ServiceClosureArgument(new Reference('system_provider1')),
                'system_provider2' => new ServiceClosureArgument(new Reference('system_provider2')),
                'system_provider3' => new ServiceClosureArgument(new Reference('system_provider3')),
                'entity_provider1' => new ServiceClosureArgument(new Reference('entity_provider1')),
                'entity_provider2' => new ServiceClosureArgument(new Reference('entity_provider2')),
                'entity_provider3' => new ServiceClosureArgument(new Reference('entity_provider3'))
            ],
            $providers->getArgument(0)
        );
        self::assertEquals(
            ['system_provider2', 'system_provider1', 'system_provider3'],
            $chainProvider->getArgument(1)
        );
        self::assertEquals(
            ['entity_provider2', 'entity_provider1', 'entity_provider3'],
            $chainProvider->getArgument(2)
        );
    }

    public function testProcessForProviderWithoutScope()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The attribute "scope" is required for "oro_email.emailtemplate.variable_provider" tag.'
            . ' Service: "system_provider1".'
        );

        $container = new ContainerBuilder();
        $container->register(self::CHAIN_PROVIDER_SERVICE)
            ->setArguments([null, [], []]);
        $container->register('system_provider1')
            ->addTag(self::PROVIDER_TAG);

        $compiler = new EmailTemplateVariablesPass();
        $compiler->process($container);
    }

    public function testProcessForProviderWithInvalidScope()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The value "another" is invalid for the tag attribute "scope" for service "system_provider1",'
            . ' expected "system" or "entity".'
        );

        $container = new ContainerBuilder();
        $container->register(self::CHAIN_PROVIDER_SERVICE)
            ->setArguments([null, [], []]);
        $container->register('system_provider1')
            ->addTag(self::PROVIDER_TAG, ['scope' => 'another']);

        $compiler = new EmailTemplateVariablesPass();
        $compiler->process($container);
    }
}
