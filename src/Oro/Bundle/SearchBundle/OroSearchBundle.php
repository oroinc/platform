<?php

namespace Oro\Bundle\SearchBundle;

use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\DoctrineConnectionPingPass;
use Oro\Component\DependencyInjection\Compiler\PriorityNamedTaggedServiceCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroSearchBundle extends Bundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new PriorityNamedTaggedServiceCompilerPass(
            'oro_search.extension.search_filter_bag',
            'oro_search.extension.search_filter.filter',
            'type'
        ));
        $container->addCompilerPass(new DoctrineConnectionPingPass('search'), PassConfig::TYPE_BEFORE_REMOVING);
    }
}
