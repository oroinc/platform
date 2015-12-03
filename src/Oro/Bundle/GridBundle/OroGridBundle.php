<?php

namespace Oro\Bundle\GridBundle;

use Oro\Bundle\GridBundle\DependencyInjection\Compiler\AddDependencyCallsCompilerPass;
use Oro\Bundle\GridBundle\DependencyInjection\Compiler\AddFilterTypeCompilerPass;

use Oro\Bundle\GridBundle\DependencyInjection\Compiler\AddFlexibleManagerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroGridBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddDependencyCallsCompilerPass());
        $container->addCompilerPass(new AddFlexibleManagerCompilerPass());
        $container->addCompilerPass(new AddFilterTypeCompilerPass());
    }
}
