<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Oro\Bundle\EntityExtendBundle\Validator\Constraints\ExtendEntityEnumValues;
use Oro\Bundle\ImportExportBundle\Validator\TypeValidationLoader as ValidationLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass that registers enum validation constraints for serialized entity fields.
 * Adds ExtendEntityEnumValues constraint to both 'enum' and 'multiEnum' field types
 * in the serialized fields validator to ensure proper validation of enum values
 * stored in serialized data.
 */
class ExtendEntitySerializedEnumValidatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $serviceId = 'oro_serialized_fields.validator.extend_entity_serialized_data';

        if (!$container->hasDefinition($serviceId)) {
            return;
        }

        $definition = $container->getDefinition($serviceId);
        $definition->addMethodCall('addConstraints', [
            'enum',
            [[ExtendEntityEnumValues::class => ['groups' => [ValidationLoader::IMPORT_FIELD_TYPE_VALIDATION_GROUP]]]]
        ]);

        $definition->addMethodCall('addConstraints', [
            'multiEnum',
            [[ExtendEntityEnumValues::class => ['groups' => [ValidationLoader::IMPORT_FIELD_TYPE_VALIDATION_GROUP]]]]
        ]);
    }
}
