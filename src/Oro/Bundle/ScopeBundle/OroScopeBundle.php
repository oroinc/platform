<?php

namespace Oro\Bundle\ScopeBundle;

use Oro\Bundle\ScopeBundle\DependencyInjection\Compiler\ScopeProviderPass;
use Oro\Bundle\ScopeBundle\DependencyInjection\OroScopeExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroScopeBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ScopeProviderPass());
    }

    /** {@inheritdoc} */
    public function getContainerExtension()
    {
        return new OroScopeExtension();
    }
}
