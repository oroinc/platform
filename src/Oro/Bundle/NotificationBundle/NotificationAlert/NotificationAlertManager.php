<?php

namespace Oro\Bundle\NotificationBundle\NotificationAlert;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\Entity\NotificationAlert;
use Oro\Bundle\NotificationBundle\Exception\NotificationAlertFetchFailedException;
use Oro\Bundle\NotificationBundle\Exception\NotificationAlertUpdateFailedException;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * The service to manage a notification alerts for a resource
 * for the user.
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

    private string $sourceType;
    private string $resourceType;
    private ManagerRegistry $doctrine;
    private TokenAccessor $tokenAccessor;

    public function __construct(
        string $sourceType,
        string $resourceType,
        ManagerRegistry $doctrine,
        TokenAccessor $tokenAccessor
    ) {
        $this->sourceType = $sourceType;
        $this->resourceType = $resourceType;
        $this->doctrine = $doctrine;
        $this->tokenAccessor = $tokenAccessor;
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
                self::RESOURCE_TYPE => $this->getResourceType(),
                self::USER          => $this->tokenAccessor->getUserId(),
                self::ORGANIZATION  => $this->tokenAccessor->getOrganizationId()
            ]
        );

        if (empty($data[self::ID])) {
            $data[self::ID] = UUIDGenerator::v4();
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

    public function resolveNotificationAlertsByAlertTypeForCurrentUser(string $alertType): void
    {
        $this->doResolveNotificationAlert([
            self::SOURCE_TYPE   => $this->getSourceType(),
            self::RESOURCE_TYPE => $this->getResourceType(),
            self::USER          => $this->tokenAccessor->getUserId(),
            self::ORGANIZATION  => $this->tokenAccessor->getOrganizationId(),
            self::ALERT_TYPE    => $alertType
        ]);
    }

    public function resolveNotificationAlertsByAlertTypeAndStepForCurrentUser(string $alertType, string $step): void
    {
        $this->doResolveNotificationAlert([
            self::SOURCE_TYPE   => $this->getSourceType(),
            self::RESOURCE_TYPE => $this->getResourceType(),
            self::USER          => $this->tokenAccessor->getUserId(),
            self::ORGANIZATION  => $this->tokenAccessor->getOrganizationId(),
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
        $hasNotificationAlertsByType = $this->doCheckHasNotificationAlertsByType([
            self::SOURCE_TYPE   => $this->getSourceType(),
            self::RESOURCE_TYPE => $this->getResourceType(),
            self::ALERT_TYPE    => $alertType,
            self::USER          => $this->tokenAccessor->getUserId(),
            self::ORGANIZATION  => $this->tokenAccessor->getOrganizationId(),
            self::RESOLVED      => false
        ]);

        return (bool) $hasNotificationAlertsByType;
    }

    private function doCheckHasNotificationAlerts(array $fields): bool
    {
        [$data, $types] = $this->prepareDbalCriteria($fields);
        $em = $this->getEntityManager();
        try {
            $sql = 'SELECT COUNT(alert.id) as notificationAlertCount
                FROM %s AS alert
                WHERE
                 alert.source_type = :source_type
                 AND alert.resource_type = :resource_type
                 AND alert.user_id = :user_id
                 AND alert.organization_id = :organization_id
                 AND alert.is_resolved = :is_resolved';
            $sql = sprintf($sql, $this->getEntityMetadata($em)->getTableName());
            $hasNotificationAlerts = $em->getConnection()->fetchOne($sql, $data, $types);
        } catch (\Exception $e) {
            throw new NotificationAlertFetchFailedException('Failed to fetch a notification alert.', $e->getCode(), $e);
        }

        return (bool) $hasNotificationAlerts;
    }

    private function doCheckHasNotificationAlertsByType(array $fields): bool
    {
        [$data, $types] = $this->prepareDbalCriteria($fields);
        $em = $this->getEntityManager();
        try {
            $sql = 'SELECT COUNT(alert.id) as notificationAlertCount
                FROM %s AS alert
                WHERE
                 alert.source_type = :source_type
                 AND alert.resource_type = :resource_type
                 AND alert.alert_type = :alert_type
                 AND alert.user_id = :user_id
                 AND alert.organization_id = :organization_id
                 AND alert.is_resolved = :is_resolved';
            $sql = sprintf($sql, $this->getEntityMetadata($em)->getTableName());
            $hasNotificationAlertsByType = $em->getConnection()->fetchOne($sql, $data, $types);
        } catch (\Exception $e) {
            throw new NotificationAlertFetchFailedException('Failed to fetch a notification alert.', $e->getCode(), $e);
        }

        return (bool) $hasNotificationAlertsByType;
    }

    /**
     * @param array $fields
     *
     * @throws NotificationAlertUpdateFailedException when the notification alert resolve failed
     */
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
        } catch (\Exception $e) {
            throw new NotificationAlertUpdateFailedException(
                'Failed to resolve a notification alert.',
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param array $fields
     *
     * @throws NotificationAlertUpdateFailedException when the notification alert insert failed
     */
    private function doInsertNotificationAlert(array $fields): void
    {
        [$data, $types] = $this->prepareDbalCriteria($fields);
        $em = $this->getEntityManager();
        $metadata = $this->getEntityMetadata($em);
        try {
            $em->getConnection()->insert($metadata->getTableName(), $data, $types);
        } catch (\Exception $e) {
            throw new NotificationAlertUpdateFailedException(
                'Failed to insert a new notification alert.',
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param array $fields
     *
     * @return string|null
     */
    private function doFindSimilarNotificationAlert(array $fields): ?string
    {
        unset(
            $fields[self::ID],
            $fields[self::MESSAGE],
            $fields[self::CREATED_AT],
            $fields[self::UPDATED_AT]
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
            throw new NotificationAlertFetchFailedException('Failed to fetch a notification alert.', $e->getCode(), $e);
        }

        return $similarNotificationAlertId ? : null;
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
        } catch (\Exception $e) {
            throw new NotificationAlertUpdateFailedException(
                'Failed to resolve a notification alert.',
                $e->getCode(),
                $e
            );
        }
    }

    private function prepareDbalCriteria(array $fields): array
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

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(NotificationAlert::class);
    }

    private function getEntityMetadata(EntityManagerInterface $em): ClassMetadata
    {
        return $em->getClassMetadata(NotificationAlert::class);
    }
}
