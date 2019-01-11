<?php

namespace Oro\Bundle\SoapBundle;

use Oro\Bundle\SoapBundle\DependencyInjection\Compiler\ApiSubRequestPass;
use Oro\Bundle\SoapBundle\DependencyInjection\Compiler\FixRestAnnotationsPass;
use Oro\Bundle\SoapBundle\DependencyInjection\Compiler\InlcudeHandlersPass;
use Oro\Bundle\SoapBundle\DependencyInjection\Compiler\MetadataProvidersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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
