<?php

namespace Oro\Bundle\ApiBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\ChainProcessor\DependencyInjection\CleanUpProcessorsCompilerPass;
use Oro\Component\ChainProcessor\DependencyInjection\LoadProcessorsCompilerPass;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\ApiDocConfigurationCompilerPass;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\ConfigurationCompilerPass;

class OroApiBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigurationCompilerPass());
        $container->addCompilerPass(
            new LoadProcessorsCompilerPass(
                'oro_api.processor_bag',
                'oro.api.processor',
                'oro.api.processor.applicable_checker'
            )
        );
        $container->addCompilerPass(
            new CleanUpProcessorsCompilerPass(
                'oro_api.simple_processor_factory',
                'oro.api.processor'
            ),
            PassConfig::TYPE_BEFORE_REMOVING
        );
        $container->addCompilerPass(
            new ApiDocConfigurationCompilerPass(),
            PassConfig::TYPE_BEFORE_REMOVING
        );
    }
}
