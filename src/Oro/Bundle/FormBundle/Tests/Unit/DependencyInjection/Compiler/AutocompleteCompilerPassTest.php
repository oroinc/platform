<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\FormBundle\DependencyInjection\Compiler\AutocompleteCompilerPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class AutocompleteCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $registry = $container->register('oro_form.autocomplete.search_registry');
        $security = $container->register('oro_form.autocomplete.security');

        $container->register('handler_1')
            ->addTag('oro_form.autocomplete.search_handler', ['alias' => 'tag1'])
            ->addTag('oro_form.autocomplete.search_handler', ['alias' => 'tag2']);
        $container->register('handler_2')
            ->addTag('oro_form.autocomplete.search_handler', ['alias' => 'tag1', 'acl_resource' => 'acl_resource_2']);
        $container->register('handler_3')
            ->addTag('oro_form.autocomplete.search_handler', ['alias' => 'tag3', 'acl_resource' => 'acl_resource_3']);

        $compiler = new AutocompleteCompilerPass();
        $compiler->process($container);

        $serviceLocatorReference = $registry->getArgument('$searchHandlers');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'tag1' => new ServiceClosureArgument(new Reference('handler_2')),
                'tag2' => new ServiceClosureArgument(new Reference('handler_1')),
                'tag3' => new ServiceClosureArgument(new Reference('handler_3'))
            ],
            $serviceLocatorDef->getArgument(0)
        );

        self::assertEquals(
            [
                'tag1' => 'acl_resource_2',
                'tag3' => 'acl_resource_3'
            ],
            $security->getArgument('$autocompleteAclResources')
        );
    }
}
