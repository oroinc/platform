<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\TypeValidationLoaderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TypeValidationLoaderPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $builderDef = $container->register('validator.builder');

        $compiler = new TypeValidationLoaderPass();
        $compiler->process($container);

        self::assertEquals(
            [
                ['addLoader', [new Reference('oro_importexport.validator.type_validation_loader')]]
            ],
            $builderDef->getMethodCalls()
        );
    }
}
