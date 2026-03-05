<?php

namespace Oro\Bundle\SanitizeBundle\Tests\Unit\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Oro\Bundle\SanitizeBundle\Provider\EntityAllMetadataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityAllMetadataProviderTest extends TestCase
{
    private ManagerRegistry|MockObject $doctrine;
    private EntityAllMetadataProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->provider = new EntityAllMetadataProvider($this->doctrine);
    }

    public function testGetAllMetadataReturnsEntityWithExistingTable(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->table = ['name' => 'some_table'];
        $metadata->method('getTableName')->willReturn('some_table');

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('listTableNames')->willReturn(['some_table', 'other_table']);

        $connection = $this->createMock(Connection::class);
        $connection->method('getSchemaManager')->willReturn($schemaManager);

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->method('getAllMetadata')->willReturn([$metadata]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getMetadataFactory')->willReturn($metadataFactory);

        $this->doctrine->method('getManagers')->willReturn(['default' => $em]);

        self::assertSame([$metadata], $this->provider->getAllMetadata());
    }

    public function testGetAllMetadataSkipsEntitiesWithoutExistingTable(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->table = [];
        $metadata->expects(self::never())->method('getTableName');

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('listTableNames')->willReturn(['some_table']);

        $connection = $this->createMock(Connection::class);
        $connection->method('getSchemaManager')->willReturn($schemaManager);

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->method('getAllMetadata')->willReturn([$metadata]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getMetadataFactory')->willReturn($metadataFactory);

        $this->doctrine->method('getManagers')->willReturn(['default' => $em]);

        self::assertSame([], $this->provider->getAllMetadata());
    }

    public function testGetAllMetadataExcludesConfigEntityManager(): void
    {
        $configEm = $this->createMock(EntityManagerInterface::class);
        $configEm->expects(self::never())->method('getConnection');
        $configEm->expects(self::never())->method('getMetadataFactory');

        $this->doctrine->method('getManagers')->willReturn(['config' => $configEm]);
        $this->provider->setConfigConnactionName('config');

        self::assertSame([], $this->provider->getAllMetadata());
    }

    public function testGetAllMetadataAcrossMultipleEntityManagers(): void
    {
        $metadata1 = $this->createMock(ClassMetadata::class);
        $metadata1->table = ['name' => 'table_1'];
        $metadata1->method('getTableName')->willReturn('table_1');

        $schemaManager1 = $this->createMock(AbstractSchemaManager::class);
        $schemaManager1->method('listTableNames')->willReturn(['table_1']);

        $connection1 = $this->createMock(Connection::class);
        $connection1->method('getSchemaManager')->willReturn($schemaManager1);

        $metadataFactory1 = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory1->method('getAllMetadata')->willReturn([$metadata1]);

        $em1 = $this->createMock(EntityManagerInterface::class);
        $em1->method('getConnection')->willReturn($connection1);
        $em1->method('getMetadataFactory')->willReturn($metadataFactory1);

        $metadata2 = $this->createMock(ClassMetadata::class);
        $metadata2->table = ['name' => 'table_2'];
        $metadata2->method('getTableName')->willReturn('table_2');

        $schemaManager2 = $this->createMock(AbstractSchemaManager::class);
        $schemaManager2->method('listTableNames')->willReturn(['table_2']);

        $connection2 = $this->createMock(Connection::class);
        $connection2->method('getSchemaManager')->willReturn($schemaManager2);

        $metadataFactory2 = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory2->method('getAllMetadata')->willReturn([$metadata2]);

        $em2 = $this->createMock(EntityManagerInterface::class);
        $em2->method('getConnection')->willReturn($connection2);
        $em2->method('getMetadataFactory')->willReturn($metadataFactory2);

        $this->doctrine->method('getManagers')->willReturn([
            'default'   => $em1,
            'secondary' => $em2,
        ]);

        self::assertSame([$metadata1, $metadata2], $this->provider->getAllMetadata());
    }
}
