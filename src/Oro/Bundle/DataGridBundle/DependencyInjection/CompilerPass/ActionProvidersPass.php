<?php

namespace Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ActionProvidersPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    const SERVICE_ID = 'oro_datagrid.extension.action';
    const EXTENSION_TAG = 'oro_datagrid.extension.action.provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices($container, self::SERVICE_ID, self::EXTENSION_TAG, 'addActionProvider');
    }
}
