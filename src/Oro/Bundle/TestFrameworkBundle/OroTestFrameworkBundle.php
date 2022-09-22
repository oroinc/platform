<?php

namespace Oro\Bundle\TestFrameworkBundle;

use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\CheckReferenceCompilerPass;
use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\ClientCompilerPass;
use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\MakeMailerMessageLoggerListenerPersistentPass;
use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\MakeMessageQueueCollectorPersistentPass;
use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\TagsInformationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroTestFrameworkBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new TagsInformationPass());
        $container->addCompilerPass(new CheckReferenceCompilerPass());
        $container->addCompilerPass(new ClientCompilerPass());
        $container->addCompilerPass(new MakeMessageQueueCollectorPersistentPass());
        $container->addCompilerPass(new MakeMailerMessageLoggerListenerPersistentPass());
    }
}
