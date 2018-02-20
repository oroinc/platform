<?php

namespace Oro\Bundle\NotificationBundle\DependencyInjection\Compiler;

use Doctrine\DBAL\Connection;
use Oro\Bundle\EntityBundle\Tools\SafeDatabaseChecker;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EventsCompilerPass implements CompilerPassInterface
{
    /** a table name of {@see Oro\Bundle\NotificationBundle\Entity\Event} */
    const EVENT_TABLE_NAME = 'oro_notification_event';

    const SERVICE_KEY    = 'oro_notification.manager';
    const DISPATCHER_KEY = 'event_dispatcher';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_KEY)) {
            return;
        }

        if (!$container->hasParameter('installed') || !$container->getParameter('installed')) {
            return;
        }

        // register event listeners
        // by performance reasons native SQL is used here rather than ORM
        // ORM usage leads unnecessary loading of Doctrine metadata and ORO entity configs which is not needed here
        /** @var Connection $connection */
        $connection = $container->get('doctrine.dbal.default_connection');
        if (SafeDatabaseChecker::tablesExist($connection, self::EVENT_TABLE_NAME)) {
            $dispatcher = $container->findDefinition(self::DISPATCHER_KEY);

            $rows = $connection->fetchAll('SELECT name FROM ' . self::EVENT_TABLE_NAME);
            foreach ($rows as $row) {
                $dispatcher->addMethodCall(
                    'addListenerService',
                    [$row['name'], [self::SERVICE_KEY, 'process']]
                );
            }
        }
    }
}
