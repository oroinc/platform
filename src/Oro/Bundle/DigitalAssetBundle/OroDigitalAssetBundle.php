<?php

namespace Oro\Bundle\DigitalAssetBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\EntityFallbackFieldsStoragePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroDigitalAssetBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new EntityFallbackFieldsStoragePass([
            'Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset' => [
                'title' => 'titles'
            ]
        ]));
    }
}
