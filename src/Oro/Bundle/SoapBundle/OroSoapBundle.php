<?php

namespace Oro\Bundle\SoapBundle;

use Oro\Bundle\SoapBundle\DependencyInjection\Compiler\LoadPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroSoapBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new LoadPass());
    }
}
