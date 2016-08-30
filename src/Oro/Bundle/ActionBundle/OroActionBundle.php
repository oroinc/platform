<?php

namespace Oro\Bundle\ActionBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ActionPass;
use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ConditionPass;
use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ConfigurationPass;
use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\MassActionProviderPass;

class OroActionBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConditionPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new ActionPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new ConfigurationPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new MassActionProviderPass(), PassConfig::TYPE_AFTER_REMOVING);
    }
}
