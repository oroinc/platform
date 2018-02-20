<?php

namespace Oro\Bundle\NotificationBundle;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Oro\Bundle\NotificationBundle\Async\Topics;
use Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\EventsCompilerPass;
use Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\NotificationHandlerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroNotificationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new NotificationHandlerPass());
        $container->addCompilerPass(new EventsCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);

        $addTopicPass = AddTopicMetaPass::create()
            ->add(Topics::SEND_NOTIFICATION_EMAIL, 'Sending email notifications')
        ;
        $container->addCompilerPass($addTopicPass);
    }
}
