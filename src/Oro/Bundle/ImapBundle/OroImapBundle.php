<?php
namespace Oro\Bundle\ImapBundle;

use Oro\Bundle\ImapBundle\Async\Topics;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroImapBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $addTopicPass = AddTopicMetaPass::create()
            ->add(Topics::SYNC_EMAIL, 'Synchronization emails via IMAP')
        ;

        $container->addCompilerPass($addTopicPass);
    }
}
