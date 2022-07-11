<?php

namespace Oro\Bundle\NotificationBundle\NotificationAlert;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\Entity\NotificationAlert;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Psr\Log\LoggerInterface;

/**
 * The service to manage a notification alerts for a resource
 * for the user.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class NotificationAlertManager
{
    public const ID = 'id';
    public const SOURCE_TYPE = 'sourceType';
    public const RESOURCE_TYPE = 'resourceType';
    public const ALERT_TYPE = 'alertType';
    public const OPERATION = 'operation';
    public const STEP = 'step';
    public const ITEM_ID = 'itemId';
    public const EXTERNAL_ID = 'externalId';
    public const USER = 'user';
    public const ORGANIZATION = 'organization';
    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updatedAt';
    public const RESOLVED = 'resolved';
    public const MESSAGE = 'message';
    public const ADDITIONAL_INFO = 'additionalInfo';

    private string $sourceType;
    private string $resourceType;
    private ManagerRegistry $doctrine;
    private TokenAccessor $tokenAccessor;
    protected LoggerInterface $logger;

    public function __construct(
        string $sourceType,
        string $resourceType,
        ManagerRegistry $doctrine,
        TokenAccessor $tokenAccessor,
        LoggerInterface $logger
    ) {
        $this->sourceType = $sourceType;
        $this->resourceType = $resourceType;
        $this->doctrine = $doctrine;
        $this->tokenAccessor = $tokenAccessor;
        $this->logger = $logger;
    }

    /**
     * Gets the type of the resource this manager works with.
     */
    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    /**
     * Gets the source type of the resource this manager works with.
     */
    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    /**
     * Saves the notification alert to the storage.
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function addNotificationAlert(NotificationAlertInterface $alert, \DateTime $syncAt = null): string
    {
        if ($alert->getSourceType() !== $this->getSourceType()) {
            throw new \BadMethodCallException(sprintf(
                'Bad manager used to store notification alert. Expected "%s" notification alert, "%s" given.',
                $this->getSourceType(),
                $alert->getSourceType()
            ));
        }

        $data = array_merge(
            $alert->toArray(),
            [
                self::SOURCE_TYPE   => $this->getSourceType(),
                self::RESOURCE_TYPE => $this->getResourceType()
            ]
        );

        if (empty($data[self::ID])) {
            $data[self::ID] = UUIDGenerator::v4();
        }

        if (empty($data[self::USER])) {
            $data[self::USER] = $this->tokenAccessor->getUserId();
        }

        if (empty($data[self::ORGANIZATION])) {
            $data[self::ORGANIZATION] = $this->tokenAccessor->getOrganizationId();
        }

        if (null !== $syncAt) {
            $data[self::CREATED_AT] = $syncAt;
        }

        if (empty($data[self::CREATED_AT])) {
            $data[self::CREATED_AT] = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        if (empty($data[self::UPDATED_AT])) {
            $data[self::UPDATED_AT] = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        $similarNotificationAlertId = $this->doFindSimilarNotificationAlert($data);
        if ($similarNotificationAlertId) {
            $data[self::ID] = $similarNotificationAlertId;
            $this->doUpdateNotificationAlert(
                $similarNotificationAlertId,
                [
                    self::MESSAGE    => $data[self::MESSAGE],
                    self::UPDATED_AT => $data[self::UPDATED_AT],
                ]
            );
        } else {
            $this->doInsertNotificationAlert($data);
        }

        return $data[self::ID];
    }

    public function resolveNotificationAlertByIdForCurrentUser(string $id): void
    {
        $this->doResolveNotificationAlert([
            self::SOURCE_TYPE   => $this->getSourceType(),
            self::RESOURCE_TYPE => $this->getResourceType(),
            self::USER          => $this->tokenAccessor->getUserId(),
            self::ORGANIZATION  => $this->tokenAccessor->getOrganizationId(),
            self::ID            => $id
        ]);
    }

    public function resolveNotificationAlertByOperationAndItemIdForCurrentUser(string $operation, string $itemId): void
    {
        $this->doResolveNotificationAlert([
            self::SOURCE_TYPE   => $this->getSourceType(),
            self::RESOURCE_TYPE => $this->getResourceType(),
            self::USER          => $this->tokenAccessor->getUserId(),
            self::ORGANIZATION  => $this->tokenAccessor->getOrganizationId(),
            self::OPERATION     => $operation,
            self::ITEM_ID       => $itemId
        ]);
    }

    public function resolveNotificationAlertByItemIdForUserAndOrganization(
        int  $itemId,
        ?int $userId,
        int  $organizationId
    ): void {
        $this->doResolveNotificationAlert([
            self::SOURCE_TYPE   => $this->getSourceType(),
            self::RESOURCE_TYPE => $this->getResourceType(),
            self::USER          => $userId,
            self::ORGANIZATION  => $organizationId,
            self::ITEM_ID       => $itemId
        ]);
    }

    public function resolveNotificationAlertsByAlertTypeForCurrentUser(string $alertType): void
    {
        $user = $this->tokenAccessor->getUserId();
        $organization = $this->tokenAccessor->getOrganizationId();
        if (null !== $user && null !== $organization) {
            $this->resolveNotificationAlertsByAlertTypeForUserAndOrganization(
                $alertType,
                $user,
                $organization
            );
        }
    }

    public function resolveNotificationAlertsByAlertTypeForUserAndOrganization(
        string $alertType,
        ?int   $userId,
        int    $organizationId
    ): void {
        $this->doResolveNotificationAlert([
            self::SOURCE_TYPE   => $this->getSourceType(),
            self::RESOURCE_TYPE => $this->getResourceType(),
            self::USER          => $userId,
            self::ORGANIZATION  => $organizationId,
            self::ALERT_TYPE    => $alertType
        ]);
    }

    public function resolveNotificationAlertsByAlertTypeAndStepForCurrentUser(string $alertType, string $step): void
    {
        $this->resolveNotificationAlertsByAlertTypeAndStepForUserAndOrganization(
            $alertType,
            $step,
            $this->tokenAccessor->getUserId(),
            $this->tokenAccessor->getOrganizationId()
        );
    }

    public function resolveNotificationAlertsByAlertTypeAndStepForUserAndOrganization(
        string $alertType,
        string $step,
        ?int   $userId,
        int    $organizationId
    ): void {
        $this->doResolveNotificationAlert([
            self::SOURCE_TYPE   => $this->getSourceType(),
            self::RESOURCE_TYPE => $this->getResourceType(),
            self::USER          => $userId,
            self::ORGANIZATION  => $organizationId,
            self::ALERT_TYPE    => $alertType,
            self::STEP          => $step
        ]);
    }

    public function hasNotificationAlerts(): bool
    {
        $hasNotificationAlerts = $this->doCheckHasNotificationAlerts([
            self::SOURCE_TYPE   => $this->getSourceType(),
            self::RESOURCE_TYPE => $this->getResourceType(),
            self::USER          => $this->tokenAccessor->getUserId(),
            self::ORGANIZATION  => $this->tokenAccessor->getOrganizationId(),
            self::RESOLVED      => false
        ]);

        return (bool) $hasNotificationAlerts;
    }

    public function hasNotificationAlertsByType(string $alertType): bool
    {
        $hasNotificationAlertsByType = $this->doCheckHasNotificationAlerts([
            self::SOURCE_TYPE   => $this->getSourceType(),
            self::RESOURCE_TYPE => $this->getResourceType(),
            self::USER          => $this->tokenAccessor->getUserId(),
            self::ORGANIZATION  => $this->tokenAccessor->getOrganizationId(),
            self::RESOLVED      => false,
            self::ALERT_TYPE    => $alertType
        ]);

        return (bool) $hasNotificationAlertsByType;
    }

    public function hasNotificationAlertsByOperationAndItemId(string $operation, int $itemId): bool
    {
        $hasNotificationAlerts = $this->doCheckHasNotificationAlerts([
            self::SOURCE_TYPE   => $this->getSourceType(),
            self::RESOURCE_TYPE => $this->getResourceType(),
            self::USER          => $this->tokenAccessor->getUserId(),
            self::ORGANIZATION  => $this->tokenAccessor->getOrganizationId(),
            self::RESOLVED      => false,
            self::OPERATION     => $operation,
            self::ITEM_ID       => $itemId
        ]);

        return (bool) $hasNotificationAlerts;
    }

    /**
     * @return array [alertType => notificationAlertCount, ...]
     */
    public function getNotificationAlertsCountGroupedByType(): array
    {
        return $this->getNotificationAlertsCountGroupedByTypeForUserAndOrganization(
            $this->tokenAccessor->getUserId(),
            $this->tokenAccessor->getOrganizationId()
        );
    }

    /**
     * @return array [alertType => notificationAlertCount, ...]
     */
    public function getNotificationAlertsCountGroupedByTypeForUserAndOrganization(
        ?int   $userId,
        int    $organizationId
    ): array {
        $result = [];
        $notificationAlerts = $this->doGetNotificationAlertsCount([
            self::SOURCE_TYPE   => $this->getSourceType(),
            self::RESOURCE_TYPE => $this->getResourceType(),
            self::USER          => $userId,
            self::ORGANIZATION  => $organizationId,
            self::RESOLVED      => false
        ]);

        foreach ($notificationAlerts as $notificationAlert) {
            $count = (int) $notificationAlert['notification_alert_count'];
            if ($count > 0) {
                $result[$notificationAlert['alert_type']] = $count;
            }
        }

        return $result;
    }

    /**
     * @return array [[alertType, notificationAlertCount], ...]
     */
    private function doGetNotificationAlertsCount(array $fields): array
    {
        if (null === $fields[self::USER]) {
            $userCondition = ' AND alert.user_id is null';
            unset($fields[self::USER]);
        } else {
            $userCondition = ' AND alert.user_id = :user_id';
        }

        [$data, $types] = $this->prepareDbalCriteria($fields);
        $em = $this->getEntityManager();
        try {
            $sql = 'SELECT alert.alert_type, COUNT(alert.id) as notification_alert_count'
                . ' FROM %s AS alert'
                . ' WHERE'
                . ' alert.source_type = :source_type'
                . ' AND alert.resource_type = :resource_type'
                . $userCondition
                . ' AND alert.organization_id = :organization_id'
                . ' AND alert.is_resolved = :is_resolved'
                . ' GROUP BY alert.alert_type';
            $sql = sprintf($sql, $this->getTableName());

            return $em->getConnection()->fetchAllAssociative($sql, $data, $types);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch a notification alerts count.', ['exception' => $e]);
        }

        return [];
    }

    private function doCheckHasNotificationAlerts(array $fields): bool
    {
        if (null === $fields[self::USER]) {
            $userCondition = ' AND alert.user_id is null';
            unset($fields[self::USER]);
        } else {
            $userCondition = ' AND alert.user_id = :user_id';
        }

        [$data, $types] = $this->prepareDbalCriteria($fields);
        $em = $this->getEntityManager();
        $hasNotificationAlerts = false;
        try {
            $sql = 'SELECT COUNT(alert.id) as notificationAlertCount FROM %s AS alert WHERE %s';
            $criteria = [];
            foreach (array_keys($data) as $columnName) {
                if ($columnName !== 'user_id') {
                    $criteria[] = 'alert.' . $columnName . ' = :' . $columnName;
                }
            }
            $criteria = implode(' AND ', $criteria);
            $criteria .= $userCondition;

            $sql = sprintf($sql, $this->getEntityMetadata($em)->getTableName(), $criteria);
            $hasNotificationAlerts = $em->getConnection()->fetchOne($sql, $data, $types);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch a notification alert.', ['exception' => $e]);
        }

        return (bool) $hasNotificationAlerts;
    }

    private function doResolveNotificationAlert(array $fields): void
    {
        [$criteria, $types] = $this->prepareDbalCriteria($fields);
        $em = $this->getEntityManager();
        $metadata = $this->getEntityMetadata($em);
        try {
            $em->getConnection()->update(
                $metadata->getTableName(),
                [
                    'updated_at'  => 'NOW()',
                    'is_resolved' => true
                ],
                $criteria,
                $types
            );

            $this->logger->notice('Notification alert was resolved.', $fields);
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to resolve a notification alert.',
                [
                    'exception' => $e,
                    'alertData' => $fields
                ]
            );
        }
    }

    private function doInsertNotificationAlert(array $fields): bool
    {
        [$data, $types] = $this->prepareDbalCriteria($fields);
        $em = $this->getEntityManager();
        $metadata = $this->getEntityMetadata($em);
        try {
            $em->getConnection()->insert($metadata->getTableName(), $data, $types);

            $this->logger->notice(
                'Notification alert was inserted.',
                ['alertData' => $fields]
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to insert a new notification alert.',
                [
                    'exception' => $e,
                    'alertData' => $fields
                ]
            );
        }

        return false;
    }

    private function doFindSimilarNotificationAlert(array $fields): ?string
    {
        unset(
            $fields[self::ID],
            $fields[self::MESSAGE],
            $fields[self::CREATED_AT],
            $fields[self::UPDATED_AT],
            $fields[self::ADDITIONAL_INFO],
        );
        $fields[self::RESOLVED] = false;

        [$data, $types] = $this->prepareDbalCriteria($fields);
        $em = $this->getEntityManager();
        try {
            $sql = 'SELECT alert.id as similarNotificationAlert FROM %s AS alert WHERE %s ORDER BY %s';
            $criteria = [];
            foreach ($data as $column => $value) {
                $criteria[] = 'alert.' . $column . ' = :' . $column;
            }
            $criteria = implode(' AND ', $criteria);
            $orderBy = 'alert.updated_at DESC, alert.created_at DESC';

            $sql = sprintf($sql, $this->getEntityMetadata($em)->getTableName(), $criteria, $orderBy);
            $similarNotificationAlertId = $em->getConnection()->fetchOne($sql, $data, $types);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch a notification alert.', ['exception' => $e]);
        }

        return $similarNotificationAlertId ?: null;
    }

    private function doUpdateNotificationAlert(string $uuid, array $fields): void
    {
        [$data, $types] = $this->prepareDbalCriteria($fields);
        $em = $this->getEntityManager();
        $metadata = $this->getEntityMetadata($em);
        try {
            $em->getConnection()->update(
                $metadata->getTableName(),
                $data,
                [self::ID => $uuid],
                $types
            );

            $this->logger->notice(
                'Notification alert was updated.',
                [
                    'alertUuid' => $uuid,
                    'alertData' => $fields
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to update a notification alert.',
                [
                    'exception' => $e,
                    'alertUuid' => $uuid,
                    'alertData' => $fields
                ]
            );
        }
    }

    protected function prepareDbalCriteria(array $fields): array
    {
        $data = [];
        $types = [];
        $em = $this->getEntityManager();
        $metadata = $this->getEntityMetadata($em);
        foreach ($fields as $fieldName => $value) {
            $isAssociation = $metadata->hasAssociation($fieldName);
            $columnName = $isAssociation
                ? $metadata->getSingleAssociationJoinColumnName($fieldName)
                : $metadata->getColumnName($fieldName);
            $data[$columnName] = $value;
            $types[$columnName] = $isAssociation
                ? Types::INTEGER
                : $metadata->getTypeOfField($fieldName);
        }

        return [$data, $types];
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(NotificationAlert::class);
    }

    protected function getTableName(): string
    {
        return $this->getEntityMetadata($this->getEntityManager())->getTableName();
    }

    private function getEntityMetadata(EntityManagerInterface $em): ClassMetadata
    {
        return $em->getClassMetadata(NotificationAlert::class);
    }
}
