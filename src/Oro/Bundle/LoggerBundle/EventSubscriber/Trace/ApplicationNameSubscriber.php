<?php

namespace Oro\Bundle\LoggerBundle\EventSubscriber\Trace;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events as DBALEvents;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\LoggerBundle\Event\SetAppNameEvent;
use Oro\Bundle\LoggerBundle\Trace\TraceManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sets PostgreSQL application_name to trace ID for database connection tracking
 * Applies to new connections on connect and updates existing connections when trace ID changes
 */
class ApplicationNameSubscriber implements EventSubscriberInterface
{
    private static ?string $appName = null;

    public function __construct(
        private readonly TraceManagerInterface $traceManager,
        private readonly ManagerRegistry $doctrine,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            DBALEvents::postConnect => [['onPostConnect', 100]],
            SetAppNameEvent::class => 'onTraceSet',
        ];
    }

    public function onPostConnect(ConnectionEventArgs $event): void
    {
        self::$appName = self::$appName ?? $this->traceManager->get();

        if (null === self::$appName) {
            return;
        }

        $this->setApplicationName($event->getConnection());
    }

    public function onTraceSet(): void
    {
        self::$appName = $this->traceManager->get();

        $connections = $this->doctrine->getConnections();
        /** @var Connection $connection */
        foreach ($connections as $connection) {
            if ($connection->isConnected()) {
                $this->setApplicationName($connection);
            }
        }
    }

    private function setApplicationName(Connection $connection): void
    {
        try {
            $platformName = $connection->getDatabasePlatform()?->getName();
            if (DatabasePlatformInterface::DATABASE_POSTGRESQL !== $platformName) {
                return;
            }

            $connection->executeStatement(
                sprintf('SET application_name TO %s', $connection->quote(self::$appName))
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to set PostgreSQL application_name',
                ['exception' => $e->getMessage(), 'traceId' => self::$appName]
            );
        }
    }
}
