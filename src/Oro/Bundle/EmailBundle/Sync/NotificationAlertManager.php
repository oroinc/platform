<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager as BaseManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Psr\Log\LoggerInterface;

/**
 * The extended notification alert manager that adds method that can return notification alerts
 * grouped by current user and system email box's alerts (the empty user).
 */
class NotificationAlertManager extends BaseManager
{
    private TokenAccessor $tokenAccessor;

    public function __construct(
        string          $sourceType,
        string          $resourceType,
        ManagerRegistry $doctrine,
        TokenAccessor   $tokenAccessor,
        LoggerInterface $logger
    ) {
        $this->tokenAccessor = $tokenAccessor;
        parent::__construct($sourceType, $resourceType, $doctrine, $tokenAccessor, $logger);
    }

    /**
     * @return array [userId => [alertType => notificationAlertCount, ...], ...]
     */
    public function getNotificationAlertsCountGroupedByUserAndType(): array
    {
        $result = [];

        [$data, $types] = $this->prepareDbalCriteria([
            self::SOURCE_TYPE   => $this->getSourceType(),
            self::RESOURCE_TYPE => $this->getResourceType(),
            self::USER          => $this->tokenAccessor->getUserId(),
            self::ORGANIZATION  => $this->tokenAccessor->getOrganizationId(),
            self::RESOLVED      => false
        ]);

        $em = $this->getEntityManager();
        $notificationAlerts = [];
        try {
            $sql = 'SELECT alert.user_id as user, alert.alert_type,'
                . ' COUNT(alert.id) as notification_alert_count'
                . ' FROM %s AS alert'
                . ' WHERE'
                . ' alert.source_type = :source_type'
                . ' AND alert.resource_type = :resource_type'
                . ' AND (alert.user_id is null or alert.user_id = :user_id)'
                . ' AND alert.organization_id = :organization_id'
                . ' AND alert.is_resolved = :is_resolved'
                . ' GROUP BY alert.user_id, alert.alert_type';
            $sql = sprintf($sql, $this->getTableName());

            $notificationAlerts = $em->getConnection()->fetchAllAssociative($sql, $data, $types);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch a notification alerts count.', ['exception' => $e]);
        }

        foreach ($notificationAlerts as $notificationAlert) {
            $count = (int)$notificationAlert['notification_alert_count'];
            if ($count > 0) {
                $result[$notificationAlert['user'] ?? 0][$notificationAlert['alert_type']] = $count;
            }
        }

        return $result;
    }
}
