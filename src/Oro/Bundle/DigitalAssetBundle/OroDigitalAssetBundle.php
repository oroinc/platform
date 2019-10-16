<?php

namespace Oro\Bundle\DigitalAssetBundle;

use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle class for OroDigitalAssetBundle.
 */
class OroDigitalAssetBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DefaultFallbackExtensionPass([DigitalAsset::class => ['title' => 'titles']]));
    }
}
