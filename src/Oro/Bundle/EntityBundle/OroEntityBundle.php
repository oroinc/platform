<?php

namespace Oro\Bundle\EntityBundle;

use Symfony\Component\ClassLoader\ClassLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;

use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityFieldHandlerPass;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\DictionaryValueListProviderPass;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityAliasProviderPass;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityClassNameProviderPass;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityNameProviderPass;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\ExclusionProviderPass;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\GeneratedValueStrategyListenerPass;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\QueryHintResolverPass;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\VirtualFieldProvidersCompilerPass;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\VirtualRelationProvidersCompilerPass;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\CustomGridFieldValidatorCompilerPass;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\DataCollectorCompilerPass;

class OroEntityBundle extends Bundle
{
    /**
     * Constructor
     *
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        // register logging hydrators class loader
        $loader = new ClassLoader();
        $loader->addPrefix(
            'OroLoggingHydrator\\',
            $kernel->getCacheDir() . DIRECTORY_SEPARATOR . 'oro_entities'
        );
        $loader->register();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new EntityAliasProviderPass());
        $container->addCompilerPass(new EntityNameProviderPass());
        $container->addCompilerPass(new EntityClassNameProviderPass());
        $container->addCompilerPass(new ExclusionProviderPass());
        $container->addCompilerPass(new VirtualFieldProvidersCompilerPass());
        $container->addCompilerPass(new VirtualRelationProvidersCompilerPass());
        $container->addCompilerPass(new DictionaryValueListProviderPass());
        $container->addCompilerPass(new QueryHintResolverPass());
        $container->addCompilerPass(new EntityFieldHandlerPass());
        $container->addCompilerPass(new CustomGridFieldValidatorCompilerPass());
        $container->addCompilerPass(new DataCollectorCompilerPass());

        if ($container instanceof ExtendedContainerBuilder) {
            $container->addCompilerPass(new GeneratedValueStrategyListenerPass());
            $container->moveCompilerPassBefore(
                'Oro\Bundle\EntityBundle\DependencyInjection\Compiler\GeneratedValueStrategyListenerPass',
                'Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterEventListenersAndSubscribersPass'
            );
        }
    }
}
