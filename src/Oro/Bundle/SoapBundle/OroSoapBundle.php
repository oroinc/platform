<?php

namespace Oro\Bundle\SoapBundle;

use Oro\Bundle\SoapBundle\DependencyInjection\Compiler\FixRestAnnotationsPass;
use Oro\Bundle\SoapBundle\DependencyInjection\Compiler\InlcudeHandlersPass;
use Oro\Bundle\SoapBundle\DependencyInjection\Compiler\MetadataProvidersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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

        /**
         * @todo remove this when https://github.com/FriendsOfSymfony/FOSRestBundle/issues/1086 will be fixed
         */
        $container->addCompilerPass(new FixRestAnnotationsPass());
    }
}
