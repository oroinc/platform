<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Tools;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\ORMException;
use Oro\Bundle\EntityBundle\Tools\SafeDatabaseChecker;

class SafeDatabaseCheckerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider tablesExistProvider
     */
    public function testTablesExist($tables, $tablesExistResult = true, $expectedResult = true)
    {
        $connection    = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $schemaManager = $this->getMockBuilder('Doctrine\DBAL\Schema\AbstractSchemaManager')
            ->disableOriginalConstructor()
            ->setMethods(['tablesExist'])
            ->getMockForAbstractClass();

        $connection->expects($this->once())
            ->method('connect');
        $connection
            ->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManager);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with($tables)
            ->willReturn($tablesExistResult);

        $this->assertSame(
            $expectedResult,
            SafeDatabaseChecker::tablesExist($connection, $tables)
        );
    }

    public function tablesExistProvider()
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
    public function testTablesExistWithEmptyTablesParam($tables)
    {
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $connection->expects($this->never())
            ->method('connect');

        $this->assertFalse(
            SafeDatabaseChecker::tablesExist($connection, $tables)
        );
    }

    public function tablesExistWithEmptyTablesParamProvider()
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
    public function testTablesExistShouldHandleExpectedExceptions($exception)
    {
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $connection->expects($this->once())
            ->method('connect')
            ->willThrowException($exception);

        $this->assertFalse(
            SafeDatabaseChecker::tablesExist($connection, 'table')
        );
    }

    public function expectedExceptionsForTablesExist()
    {
        return [
            [new \PDOException()],
            [new DBALException()],
        ];
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage unexpected
     */
    public function testTablesExistShouldRethrowUnexpectedException()
    {
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $connection->expects($this->once())
            ->method('connect')
            ->willThrowException(new \Exception('unexpected'));

        SafeDatabaseChecker::tablesExist($connection, 'table');
    }

    public function testGetTableName()
    {
        $doctrine      = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $em            = $this->createMock('Doctrine\ORM\EntityManagerInterface');
        $classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $entityName = 'Test\Entity';
        $tableName  = 'test_table';

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

    public function testGetTableNameForNotOrmEntity()
    {
        $doctrine = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $om       = $this->createMock('Doctrine\Common\Persistence\ObjectManager');

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
    public function testGetTableNameWithEmptyEntityNameParam($entityName)
    {
        $doctrine = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');

        $doctrine->expects($this->never())
            ->method('getManagerForClass');

        $this->assertNull(
            SafeDatabaseChecker::getTableName($doctrine, $entityName)
        );
    }

    public function getTableNameWithEmptyEntityNameParamProvider()
    {
        return [
            [null],
            [''],
        ];
    }

    /**
     * @dataProvider expectedExceptionsForGetTableName
     */
    public function testGetTableNameShouldHandleExpectedExceptions($exception)
    {
        $doctrine = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');

        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->willThrowException($exception);

        $this->assertNull(
            SafeDatabaseChecker::getTableName($doctrine, 'Test\Entity')
        );
    }

    public function expectedExceptionsForGetTableName()
    {
        return [
            [new \PDOException()],
            [new DBALException()],
            [new ORMException()],
            [new \ReflectionException()],
        ];
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage unexpected
     */
    public function testGetTableNameShouldRethrowUnexpectedException()
    {
        $doctrine = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');

        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->willThrowException(new \Exception('unexpected'));

        SafeDatabaseChecker::getTableName($doctrine, 'Test\Entity');
    }

    public function testGetAllMetadata()
    {
        $om              = $this->createMock('Doctrine\Common\Persistence\ObjectManager');
        $metadataFactory = $this->createMock('Doctrine\Common\Persistence\Mapping\ClassMetadataFactory');

        $classMetadata = $this->createMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');

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
    public function testGetAllMetadataShouldHandleExpectedExceptions($exception)
    {
        $om = $this->createMock('Doctrine\Common\Persistence\ObjectManager');

        $om->expects($this->once())
            ->method('getMetadataFactory')
            ->willThrowException($exception);

        $this->assertSame(
            [],
            SafeDatabaseChecker::getAllMetadata($om)
        );
    }

    public function expectedExceptionsForGetAllMetadata()
    {
        return [
            [new \PDOException()],
            [new DBALException()],
            [new ORMException()],
            [new \ReflectionException()],
        ];
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage unexpected
     */
    public function testGetAllMetadataShouldRethrowUnexpectedException()
    {
        $om = $this->createMock('Doctrine\Common\Persistence\ObjectManager');

        $om->expects($this->once())
            ->method('getMetadataFactory')
            ->willThrowException(new \Exception('unexpected'));

        SafeDatabaseChecker::getAllMetadata($om);
    }
}
