<?php

namespace Oro\Bundle\SyncBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\ClankSessionHandlerConfigurationPass;
use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\ClankClientPingConfigurationPass;

class OroSyncBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        // register this compiler pass on "before removing" stage because parameters are resolved on "optimize" stage
        $container->addCompilerPass(new ClankSessionHandlerConfigurationPass(), PassConfig::TYPE_BEFORE_REMOVING);

        $container->addCompilerPass(new ClankClientPingConfigurationPass());
    }
}
