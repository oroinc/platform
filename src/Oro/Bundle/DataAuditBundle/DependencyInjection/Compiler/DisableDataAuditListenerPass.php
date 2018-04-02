<?php

namespace Oro\Bundle\DataAuditBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Disables collecting data audit data if the application is not installed yet.
 */
class DisableDataAuditListenerPass implements CompilerPassInterface
{
    const DATA_AUDIT_LISTENER_SERVICE_ID = 'oro_dataaudit.listener.send_changed_entities_to_message_queue';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $isInstalled = $container->hasParameter('installed') && $container->getParameter('installed');
        if (!$isInstalled && $container->hasDefinition(self::DATA_AUDIT_LISTENER_SERVICE_ID)) {
            $dataAuditListenerDef = $container->getDefinition(self::DATA_AUDIT_LISTENER_SERVICE_ID);
            $dataAuditListenerDef->addMethodCall('setEnabled', [false]);
        }
    }
}
