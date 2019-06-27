<?php

namespace Oro\Bundle\ActionBundle;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ActionLocatorPass;
use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ActionPass;
use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ButtonProviderPass;
use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ConditionLocatorPass;
use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ConditionPass;
use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\DoctrineTypeMappingProviderPass;
use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\DuplicatorFilterPass;
use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\DuplicatorMatcherPass;
use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\MassActionProviderPass;
use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\OperationRegistryFilterPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The ActionBundle bundle class.
 */
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
        $container->addCompilerPass(new MassActionProviderPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new ButtonProviderPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new DoctrineTypeMappingProviderPass());
        $container->addCompilerPass(new OperationRegistryFilterPass());
        $container->addCompilerPass(new DuplicatorFilterPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new DuplicatorMatcherPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new ActionLocatorPass());
        $container->addCompilerPass(new ConditionLocatorPass());
    }
}
