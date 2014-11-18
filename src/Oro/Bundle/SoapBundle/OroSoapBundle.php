<?php

namespace Oro\Bundle\SoapBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\SoapBundle\DependencyInjection\Compiler\LoadPass;
use Oro\Bundle\SoapBundle\DependencyInjection\Compiler\InlcudeHandlersPass;

class OroSoapBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new LoadPass());
        $container->addCompilerPass(new InlcudeHandlersPass());
    }
}
