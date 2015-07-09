<?php

namespace Oro\Bundle\EntityBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\DoctrineSqlFiltersConfigurationPass;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\DictionaryValueListProviderPass;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityAliasProviderPass;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityClassNameProviderPass;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityNameProviderPass;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\ExclusionProviderPass;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\VirtualFieldProvidersCompilerPass;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\VirtualRelationProvidersCompilerPass;

class OroEntityBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new DoctrineSqlFiltersConfigurationPass());
        $container->addCompilerPass(new EntityAliasProviderPass());
        $container->addCompilerPass(new EntityNameProviderPass());
        $container->addCompilerPass(new EntityClassNameProviderPass());
        $container->addCompilerPass(new ExclusionProviderPass());
        $container->addCompilerPass(new VirtualFieldProvidersCompilerPass());
        $container->addCompilerPass(new VirtualRelationProvidersCompilerPass());
        $container->addCompilerPass(new DictionaryValueListProviderPass());
    }
}
