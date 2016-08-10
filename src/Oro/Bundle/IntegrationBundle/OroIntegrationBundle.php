<?php

namespace Oro\Bundle\IntegrationBundle;

use Oro\Bundle\IntegrationBundle\Async\Topics;
use Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass\DeleteIntegrationProvidersPass;
use Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass\ProcessorsPass;
use Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass\SettingsPass;
use Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass\TypesPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroIntegrationBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TypesPass());
        $container->addCompilerPass(new DeleteIntegrationProvidersPass());
        $container->addCompilerPass(new SettingsPass());
        $container->addCompilerPass(new ProcessorsPass());

        $addTopicPass = AddTopicMetaPass::create()
            ->add(Topics::SYNC_INTEGRATION)
            ->add(Topics::REVERS_SYNC_INTEGRATION)
        ;
        $container->addCompilerPass($addTopicPass);
    }
}
