<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityValidationLoaderCompilerPass;
use Oro\Bundle\EntityBundle\Validator\EntityValidationLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class EntityValidationLoaderCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $validatorBuilderDef = $container->register('validator.builder');

        $compiler = new EntityValidationLoaderCompilerPass();
        $compiler->process($container);

        self::assertTrue($container->hasDefinition('oro_entity.entity_validation_loader'));
        self::assertEquals(
            new Definition(EntityValidationLoader::class, [new Reference('doctrine')]),
            $container->getDefinition('oro_entity.entity_validation_loader')
        );
        self::assertEquals(
            [
                ['addLoader', [new Reference('oro_entity.entity_validation_loader')]]
            ],
            $validatorBuilderDef->getMethodCalls()
        );
    }
}
