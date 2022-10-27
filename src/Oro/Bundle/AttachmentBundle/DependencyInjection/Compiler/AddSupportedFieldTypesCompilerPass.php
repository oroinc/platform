<?php

namespace Oro\Bundle\AttachmentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds file, image, multiFile and multiImage field types to the list of supported types.
 */
class AddSupportedFieldTypesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        $container->getDefinition('oro_entity_extend.field_type_provider')
            ->addMethodCall('addSupportedFieldType', ['file'])
            ->addMethodCall('addSupportedFieldType', ['image'])
            ->addMethodCall('addSupportedFieldType', ['multiFile'])
            ->addMethodCall('addSupportedFieldType', ['multiImage']);
    }
}
