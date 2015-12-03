<?php

namespace Oro\Bundle\EntityConfigBundle;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler\EntityConfigPass;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler\ServiceLinkPass;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler\ServiceMethodPass;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroEntityConfigBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ServiceLinkPass);
        $container->addCompilerPass(new ServiceMethodPass);
        $container->addCompilerPass(new EntityConfigPass);
    }
}
