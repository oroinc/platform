<?php

namespace Oro\Bundle\ScopeBundle;

use Oro\Bundle\ScopeBundle\DependencyInjection\Compiler\ScopeProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroScopeBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ScopeProviderPass());
    }
}
