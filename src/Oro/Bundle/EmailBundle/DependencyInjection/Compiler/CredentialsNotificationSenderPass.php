<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects the notification channels that will send the notifications about wrong credential sync email boxes.
 */
class CredentialsNotificationSenderPass implements CompilerPassInterface
{
    const SERVICE_KEY = 'oro_email.credentials.issue_manager';
    const NOTIFICATION_CHANNEL_TAG = 'oro_email.credentials.notification_sender';
    const NOTIFICATION_USER_CHANNEL_TAG = 'oro_email.credentials.user_notification_sender';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_KEY)) {
            return;
        }

        $selectorDef = $container->getDefinition(self::SERVICE_KEY);

        $taggedServices = $container->findTaggedServiceIds(self::NOTIFICATION_CHANNEL_TAG);
        foreach ($taggedServices as $loaderServiceId => $tagAttributes) {
            $selectorDef->addMethodCall('addNotificationSender', array(new Reference($loaderServiceId)));
        }

        $taggedServices = $container->findTaggedServiceIds(self::NOTIFICATION_USER_CHANNEL_TAG);
        foreach ($taggedServices as $loaderServiceId => $tagAttributes) {
            $selectorDef->addMethodCall('addUserNotificationSender', array(new Reference($loaderServiceId)));
        }
    }
}
