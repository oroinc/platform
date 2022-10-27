<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SQLAzurePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\ScopeBundle\EventListener\RowHashCommentMetadataListener;
use Oro\Bundle\ScopeBundle\Migration\AddCommentToRowHashManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RowHashCommentMetadataListenerTest extends TestCase
{
    private const RELATIONS = 'customer_id,customergroup_id,localization_id';

    /** @var LoadClassMetadataEventArgs|MockObject */
    private $event;

    /** @var RowHashCommentMetadataListener */
    private $listener;

    protected function setUp(): void
    {
        $this->event = $this->createMock(LoadClassMetadataEventArgs::class);

        $manager = $this->createMock(AddCommentToRowHashManager::class);
        $manager->expects($this->any())
            ->method('getRelations')
            ->willReturn(self::RELATIONS);

        $this->listener = new RowHashCommentMetadataListener($manager);
    }

    public function testPlatformNotSupported(): void
    {
        $platform = new SQLAzurePlatform();
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->event->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->listener->loadClassMetadata($this->event);
    }

    public function testNotScopeTable(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new PostgreSqlPlatform());

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $metadata = $this->createMock(ClassMetadataInfo::class);
        $metadata->expects($this->once())
            ->method('getTableName')
            ->willReturn('not_oro_scope');

        $this->event->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);
        $this->event->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $this->listener->loadClassMetadata($this->event);
    }

    public function testLoadClassMetadata(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new PostgreSqlPlatform());

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $metadata = $this->createMock(ClassMetadataInfo::class);
        $mappingData = [
            'fieldName' => 'rowHash',
            'type' => 'string',
            'scale' => 0,
            'length' => 32,
            'unique' => 1,
            'nullable' =>'',
            'precision' => 0,
            'columnName' => 'row_hash',
            'options' => [
                'comment' => self::RELATIONS
            ]
        ];
        $metadata->fieldMappings = [
            'rowHash' => $mappingData
        ];

        $metadata->expects($this->once())
            ->method('getTableName')
            ->willReturn('oro_scope');
        $metadata->expects($this->once())
            ->method('setAttributeOverride')
            ->with('rowHash', $mappingData)
            ->willReturn(null);

        $this->event->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);
        $this->event->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $this->listener->loadClassMetadata($this->event);
    }
}
