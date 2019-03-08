<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\EntityExtendPass;
use Oro\Bundle\EntityExtendBundle\Validator\Validation;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EntityExtendPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $validationBuilder = $container->register('validator.builder');

        $compiler = new EntityExtendPass();
        $compiler->process($container);

        self::assertEquals(
            [Validation::class, 'createValidatorBuilder'],
            $validationBuilder->getFactory()
        );
        self::assertEquals(
            [
                ['addCustomLoader', [new Reference('oro_entity_extend.validation_loader')]]
            ],
            $validationBuilder->getMethodCalls()
        );
    }
}
