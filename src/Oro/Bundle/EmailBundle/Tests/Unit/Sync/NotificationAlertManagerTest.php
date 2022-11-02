<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Sync\NotificationAlertManager;
use Oro\Bundle\NotificationBundle\Entity\NotificationAlert;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class NotificationAlertManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var TokenAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var NotificationAlertManager */
    private $notificationAlertManager;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->connection = $this->createMock(Connection::class);
        $this->tokenAccessor = $this->createMock(TokenAccessor::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(NotificationAlert::class)
            ->willReturn($this->em);
        $this->em->expects(self::any())
            ->method('getConnection')
            ->willReturn($this->connection);
        $this->em->expects(self::any())
            ->method('getClassMetadata')
            ->with(NotificationAlert::class)
            ->willReturn($this->mockMetadata());
        $this->connection->expects(self::any())
            ->method('convertToPHPValue')
            ->willReturnCallback(function ($value, $type) {
                if (null === $value) {
                    return null;
                }
                if ('integer' === $type) {
                    return (int)$value;
                }
                if ('boolean' === $type) {
                    return (bool)$value;
                }
                if ('datetime' === $type) {
                    return $this->createDateTime($value);
                }

                return $value;
            });

        $this->notificationAlertManager = new NotificationAlertManager(
            'test_integration',
            'test_resource',
            $doctrine,
            $this->tokenAccessor,
            $this->logger
        );
    }

    private function mockMetadata(): MockObject
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::any())
            ->method('getTableName')
            ->willReturn('oro_notification_alert');
        $metadata->expects(self::any())
            ->method('getTypeOfField')
            ->willReturnMap([
                ['id', 'guid'],
                ['alertType', 'text'],
                ['sourceType', 'text'],
                ['resourceType', 'text'],
                ['createdAt', 'datetime'],
                ['updatedAt', 'datetime'],
                ['operation', 'text'],
                ['step', 'text'],
                ['itemId', 'integer'],
                ['externalId', 'text'],
                ['message', 'text'],
                ['resolved', 'boolean'],
                ['additional_data', 'array']
            ]);
        $metadata->expects(self::any())
            ->method('getColumnName')
            ->willReturnMap([
                ['id', 'id'],
                ['alertType', 'alert_type'],
                ['sourceType', 'source_type'],
                ['resourceType', 'resource_type'],
                ['createdAt', 'created_at'],
                ['updatedAt', 'updated_at'],
                ['operation', 'operation'],
                ['step', 'step'],
                ['itemId', 'item_id'],
                ['externalId', 'external_id'],
                ['message', 'message'],
                ['resolved', 'is_resolved'],
                ['additional_data', 'additional_data']
            ]);
        $metadata->expects(self::any())
            ->method('getFieldName')
            ->willReturnMap([
                ['id', 'id'],
                ['alert_type', 'errorType'],
                ['source_type', 'sourceType'],
                ['resource_type', 'resourceType'],
                ['created_at', 'createdAt'],
                ['updated_at', 'updatedAt'],
                ['operation', 'operation'],
                ['step', 'step'],
                ['item_id', 'itemId'],
                ['external_id', 'externalId'],
                ['message', 'message'],
                ['is_resolved', 'resolved'],
                ['additionalData', 'additional_data'],
            ]);
        $metadata->expects(self::any())
            ->method('getSingleAssociationJoinColumnName')
            ->willReturnMap([
                ['user', 'user_id'],
                ['organization', 'organization_id']
            ]);
        $metadata->expects(self::any())
            ->method('hasAssociation')
            ->willReturnCallback(function ($name) {
                return in_array($name, ['user', 'organization'], true);
            });

        return $metadata;
    }

    public function testGetNotificationAlertsCountGroupedByUserAndType(): void
    {
        $userId = 10;
        $organizationId = 1;

        $this->tokenAccessor->expects(self::once())
            ->method('getUserId')
            ->willReturn($userId);
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganizationId')
            ->willReturn($organizationId);

        $this->connection->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                'SELECT alert.user_id as user, alert.alert_type,'
                . ' COUNT(alert.id) as notification_alert_count'
                . ' FROM oro_notification_alert AS alert'
                . ' WHERE'
                . ' alert.source_type = :source_type'
                . ' AND alert.resource_type = :resource_type'
                . ' AND (alert.user_id is null or alert.user_id = :user_id)'
                . ' AND alert.organization_id = :organization_id'
                . ' AND alert.is_resolved = :is_resolved'
                . ' GROUP BY alert.user_id, alert.alert_type',
                [
                    'source_type'     => 'test_integration',
                    'resource_type'   => 'test_resource',
                    'user_id'         => 10,
                    'organization_id' => 1,
                    'is_resolved'     => false,
                ],
                [
                    'source_type'     => 'text',
                    'resource_type'   => 'text',
                    'user_id'         => 'integer',
                    'organization_id' => 'integer',
                    'is_resolved'     => 'boolean',
                ]
            )
            ->willReturn([
                ['user' => 0, 'alert_type' => 'auth', 'notification_alert_count' => 1],
                ['user' => 0, 'alert_type' => 'switch folder', 'notification_alert_count' => 2],
                ['user' => 0, 'alert_type' => 'sync', 'notification_alert_count' => 3],
                ['user' => 10, 'alert_type' => 'auth', 'notification_alert_count' => 4],
                ['user' => 10, 'alert_type' => 'switch folder', 'notification_alert_count' => 5],
                ['user' => 10, 'alert_type' => 'sync', 'notification_alert_count' => 6],
            ]);

        self::assertEquals(
            [
                0 => [
                    'auth' => 1,
                    'switch folder' => 2,
                    'sync' => 3
                ],
                10 => [
                    'auth' => 4,
                    'switch folder' => 5,
                    'sync' => 6
                ]
            ],
            $this->notificationAlertManager->getNotificationAlertsCountGroupedByUserAndType()
        );
    }

    public function testGetNotificationAlertsCountGroupedByUserAndTypeWithException(): void
    {
        $exception = new \Exception('Error during fetch by error type.', 510);

        $this->connection->expects(self::once())
            ->method('fetchAllAssociative')
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Failed to fetch a notification alerts count.', ['exception' => $exception]);

        $this->notificationAlertManager->getNotificationAlertsCountGroupedByUserAndType();
    }
}
