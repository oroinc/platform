<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OperationRegistryFilterPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;
    
    const OPERATION_REGISTRY_SERVICE_ID = 'oro_action.operation_registry';
    const OPERATION_REGISTRY_FILTER_TAG = 'oro_action.operation_registry.filter';

    /** {@inheritdoc} */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices(
            $container,
            self::OPERATION_REGISTRY_SERVICE_ID,
            self::OPERATION_REGISTRY_FILTER_TAG,
            'addFilter'
        );
    }
}
