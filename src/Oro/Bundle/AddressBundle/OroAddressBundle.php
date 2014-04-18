<?php

namespace Oro\Bundle\AddressBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\AddressBundle\DependencyInjection\Compiler\AddressProviderPass;

class OroAddressBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddressProviderPass());
    }
}
