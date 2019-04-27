<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Oro\Bundle\EntityExtendBundle\Validator\Validation;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides Symfony validation factory to be able to custom validation loaders.
 */
class EntityExtendPass implements CompilerPassInterface
{
    private const VALIDATION_BUILDER_SERVICE_ID       = 'validator.builder';
    private const EXTEND_VALIDATION_LOADER_SERVICE_ID = 'oro_entity_extend.validation_loader';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::VALIDATION_BUILDER_SERVICE_ID)) {
            $container->getDefinition(self::VALIDATION_BUILDER_SERVICE_ID)
                ->setFactory([Validation::class, 'createValidatorBuilder'])
                ->addMethodCall('addCustomLoader', [new Reference(self::EXTEND_VALIDATION_LOADER_SERVICE_ID)]);
        }
    }
}
