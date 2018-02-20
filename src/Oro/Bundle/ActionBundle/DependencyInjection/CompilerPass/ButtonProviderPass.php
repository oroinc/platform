<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ButtonProviderPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    const EXTENSION_TAG = 'oro.action.extension.button_provider';
    const SERVICE_ID = 'oro_action.provider.button';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices($container, self::SERVICE_ID, self::EXTENSION_TAG, 'addExtension');
    }
}
