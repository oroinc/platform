<?php

namespace Oro\Bundle\ImapBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\ImapBundle\Async\Topics;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;

class OroImapBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $addTopicPass = AddTopicMetaPass::create()
            ->add(Topics::CLEAR_INACTIVE_MAILBOX, 'Clear inactive mailbox')
            ->add(Topics::SYNC_EMAIL, 'Synchronization emails via IMAP')
        ;
        $container->addCompilerPass($addTopicPass);
    }
}
