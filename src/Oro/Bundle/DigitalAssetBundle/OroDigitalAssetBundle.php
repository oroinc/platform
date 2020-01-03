<?php

namespace Oro\Bundle\DigitalAssetBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The DigitalAssetBundle bundle class.
 */
class OroDigitalAssetBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new DefaultFallbackExtensionPass([
            'Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset' => [
                'title' => 'titles'
            ]
        ]));
    }
}
