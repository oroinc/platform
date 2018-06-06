<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Oro\Bundle\EntityExtendBundle\DependencyInjection\EntityExtendConfiguration;
use Oro\Bundle\EntityExtendBundle\Validator\Validation;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EntityExtendPass implements CompilerPassInterface
{
    const FIELD_TYPE_HELPER_SERVICE_ID = 'oro_entity_extend.extend.field_type_helper';

    const EXTEND_VALIDATION_LOADER_ID = 'oro_entity_extend.validation_loader';

    const ENTITY_EXTEND_FILE_ROOT_NODE = 'entity_extend';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_entity_extend',
            new YamlCumulativeFileLoader('Resources/config/oro/entity_extend.yml')
        );
        $resources    = $configLoader->load($container);
        $configs      = [];
        foreach ($resources as $resource) {
            $configs[] = $resource->data[self::ENTITY_EXTEND_FILE_ROOT_NODE];
        }
        $processor = new Processor();
        $config    = $processor->processConfiguration(new EntityExtendConfiguration(), $configs);

        if ($container->hasDefinition(self::FIELD_TYPE_HELPER_SERVICE_ID)) {
            $fieldTypeHelperDef = $container->getDefinition(self::FIELD_TYPE_HELPER_SERVICE_ID);
            $fieldTypeHelperDef->replaceArgument(0, $config['underlying_types']);
        }

        if ($container->hasDefinition('validator.builder')) {
            $validatorBuilder = $container->getDefinition('validator.builder');
            $validatorBuilder->setFactory(sprintf('%s::createValidatorBuilder', Validation::class));
            $validatorBuilder->addMethodCall('addCustomLoader', [new Reference(self::EXTEND_VALIDATION_LOADER_ID)]);
        }
    }
}
