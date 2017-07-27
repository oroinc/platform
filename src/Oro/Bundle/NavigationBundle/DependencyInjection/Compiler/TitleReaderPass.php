<?php

namespace Oro\Bundle\NavigationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;

class TitleReaderPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;

    const REGISTRY_TAG = 'oro_navigation.title_reader.registry';
    const READER_TAG = 'oro_navigation.title_reader';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices($container, self::REGISTRY_TAG, self::READER_TAG, 'addTitleReader');
    }
}
