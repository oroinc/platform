<?php

namespace Oro\Bundle\AddressBundle;

use Oro\Bundle\AddressBundle\DependencyInjection\Compiler\PhoneProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The AddressBundle bundle class.
 */
class OroAddressBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new PhoneProviderPass());
    }
}
