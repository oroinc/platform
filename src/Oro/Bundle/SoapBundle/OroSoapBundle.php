<?php

namespace Oro\Bundle\SoapBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\SoapBundle\DependencyInjection\Compiler\ApiSubRequestPass;
use Oro\Bundle\SoapBundle\DependencyInjection\Compiler\InlcudeHandlersPass;
use Oro\Bundle\SoapBundle\DependencyInjection\Compiler\MetadataProvidersPass;
use Oro\Bundle\SoapBundle\DependencyInjection\Compiler\FixRestAnnotationsPass;

/**
 * The SoapBundle bundle class.
 */
class OroSoapBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new InlcudeHandlersPass());
        $container->addCompilerPass(new MetadataProvidersPass());
        $container->addCompilerPass(new FixRestAnnotationsPass());
        $container->addCompilerPass(new ApiSubRequestPass());
    }
}
