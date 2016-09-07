<?php

namespace Oro\Bundle\FeatureToggleBundle;

use Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass\ConfigurationPass;
use Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass\FeatureToggleablePass;
use Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass\FeatureToggleVotersPass;
use Oro\Bundle\FeatureToggleBundle\DependencyInjection\OroFeatureToggleExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroFeatureToggleBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function getContainerExtension()
    {
        return new OroFeatureToggleExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigurationPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new FeatureToggleVotersPass());
        $container->addCompilerPass(new FeatureToggleablePass());
    }
}
