<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\Tools\SafeDatabaseChecker;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SafeDatabaseCheckerTest extends TestCase
{
    /**
     * @dataProvider tablesExistProvider
     */
    public function testTablesExist($tables, bool $tablesExistResult = true, bool $expectedResult = true): void
    {
        $connection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $connection->expects($this->once())
            ->method('connect');
        $connection->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager);
        $schemaManager->expects($this->once())
            ->method('tablesExist')
            ->with($tables)
            ->willReturn($tablesExistResult);

        $this->assertSame(
            $expectedResult,
            SafeDatabaseChecker::tablesExist($connection, $tables)
        );
    }

    public function tablesExistProvider(): array
    {
        return [
            ['table1'],
            [['table1']],
            [['table1', 'table2']],
            ['table1', false, false],
        ];
    }

    /**
     * @dataProvider tablesExistWithEmptyTablesParamProvider
     */
    public function testTablesExistWithEmptyTablesParam($tables): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())
            ->method('connect');

        $this->assertFalse(
            SafeDatabaseChecker::tablesExist($connection, $tables)
        );
    }

    public function tablesExistWithEmptyTablesParamProvider(): array
    {
        return [
            [null],
            [''],
            [[]],
        ];
    }

    /**
     * @dataProvider expectedExceptionsForTablesExist
     */
    public function testTablesExistShouldHandleExpectedExceptions(\Throwable $exception): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('connect')
            ->willThrowException($exception);

        $this->assertFalse(
            SafeDatabaseChecker::tablesExist($connection, 'table')
        );
    }

    public function expectedExceptionsForTablesExist(): array
    {
        return [
            [new \PDOException()],
            [new Exception()],
        ];
    }

    public function testTablesExistShouldRethrowUnexpectedException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('unexpected');

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('connect')
            ->willThrowException(new \Exception('unexpected'));

        SafeDatabaseChecker::tablesExist($connection, 'table');
    }

    public function testGetTableName(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $classMetadata = $this->createMock(\Doctrine\ORM\Mapping\ClassMetadata::class);

        $entityName = 'Test\Entity';
        $tableName = 'test_table';

        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with($entityName)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);
        $classMetadata->expects($this->once())
            ->method('getTableName')
            ->willReturn($tableName);

        $this->assertEquals(
            $tableName,
            SafeDatabaseChecker::getTableName($doctrine, $entityName)
        );
    }

    public function testGetTableNameForNotOrmEntity(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $om = $this->createMock(ObjectManager::class);

        $entityName = 'Test\Entity';

        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with($entityName)
            ->willReturn($om);
        $om->expects($this->never())
            ->method('getClassMetadata');

        $this->assertNull(
            SafeDatabaseChecker::getTableName($doctrine, $entityName)
        );
    }

    /**
     * @dataProvider getTableNameWithEmptyEntityNameParamProvider
     */
    public function testGetTableNameWithEmptyEntityNameParam(?string $entityName): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);

        $doctrine->expects($this->never())
            ->method('getManagerForClass');

        $this->assertNull(
            SafeDatabaseChecker::getTableName($doctrine, $entityName)
        );
    }

    public function getTableNameWithEmptyEntityNameParamProvider(): array
    {
        return [
            [null],
            [''],
        ];
    }

    /**
     * @dataProvider expectedExceptionsForGetTableName
     */
    public function testGetTableNameShouldHandleExpectedExceptions(\Throwable $exception): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);

        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->willThrowException($exception);

        $this->assertNull(
            SafeDatabaseChecker::getTableName($doctrine, 'Test\Entity')
        );
    }

    public function expectedExceptionsForGetTableName(): array
    {
        return [
            [new \PDOException()],
            [new Exception()],
            [new ORMException()],
            [new \ReflectionException()],
        ];
    }

    public function testGetTableNameShouldRethrowUnexpectedException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('unexpected');

        $doctrine = $this->createMock(ManagerRegistry::class);

        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->willThrowException(new \Exception('unexpected'));

        SafeDatabaseChecker::getTableName($doctrine, 'Test\Entity');
    }

    public function testGetAllMetadata(): void
    {
        $om = $this->createMock(ObjectManager::class);
        $metadataFactory = $this->createMock(ClassMetadataFactory::class);

        $classMetadata = $this->createMock(ClassMetadata::class);

        $allMetadata = [$classMetadata];

        $om->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn($allMetadata);

        $this->assertSame(
            $allMetadata,
            SafeDatabaseChecker::getAllMetadata($om)
        );
    }

    /**
     * @dataProvider expectedExceptionsForGetAllMetadata
     */
    public function testGetAllMetadataShouldHandleExpectedExceptions(\Throwable $exception): void
    {
        $om = $this->createMock(ObjectManager::class);

        $om->expects($this->once())
            ->method('getMetadataFactory')
            ->willThrowException($exception);

        $this->assertSame(
            [],
            SafeDatabaseChecker::getAllMetadata($om)
        );
    }

    public function expectedExceptionsForGetAllMetadata(): array
    {
        return [
            [new \PDOException()],
            [new Exception()],
            [new ORMException()],
            [new \ReflectionException()],
        ];
    }

    public function testGetAllMetadataShouldRethrowUnexpectedException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('unexpected');

        $om = $this->createMock(ObjectManager::class);

        $om->expects($this->once())
            ->method('getMetadataFactory')
            ->willThrowException(new \Exception('unexpected'));

        SafeDatabaseChecker::getAllMetadata($om);
    }
}
