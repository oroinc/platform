<?php

namespace Oro\Bundle\SearchBundle;

use Oro\Component\DependencyInjection\Compiler\PriorityNamedTaggedServiceCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The SearchBundle bundle class.
 */
class OroSearchBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new PriorityNamedTaggedServiceCompilerPass(
            'oro_search.extension.search_filter_bag',
            'oro_search.extension.search_filter.filter',
            'type'
        ));
    }
}
