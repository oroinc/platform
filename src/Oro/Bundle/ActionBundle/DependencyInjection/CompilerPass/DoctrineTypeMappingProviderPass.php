<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;

class DoctrineTypeMappingProviderPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    const EXTENSION_TAG = 'oro.action.extension.doctrine_type_mapping';
    const SERVICE_ID = 'oro_action.provider.doctrine_type_mapping';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices($container, self::SERVICE_ID, self::EXTENSION_TAG, 'addExtension');
    }
}
