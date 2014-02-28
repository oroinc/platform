<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;

use Migration\v1_0\Test1BundleMigration10;
use Migration\v1_1\Test1BundleMigration11;
use TestPackage\src\WrongTableNameMigration;
use TestPackage\src\WrongColumnNameMigration;

use Oro\Bundle\MigrationBundle\Migration\MigrationQueryBuilder;

class MigrationQueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var MigrationQueryBuilder */
    protected $builder;

    protected $em;

    protected $connection;

    public function setUp()
    {
        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em         = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->connection));

        $platform = new MySqlPlatform();
        $schema   = new Schema();
        $sm       = $this->getMockBuilder('Doctrine\DBAL\Schema\AbstractSchemaManager')
            ->disableOriginalConstructor()
            ->setMethods(['createSchema'])
            ->getMockForAbstractClass();
        $sm->expects($this->once())
            ->method('createSchema')
            ->will($this->returnValue($schema));
        $this->connection->expects($this->once())
            ->method('getSchemaManager')
            ->will($this->returnValue($sm));
        $this->connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($platform));

        $this->builder = new MigrationQueryBuilder($this->connection);
    }

    public function testGetMigrationsQueries()
    {
        $this->IncludeFile('Test1Bundle/Migrations/Schema/v1_0/Test1BundleMigration10.php');
        $this->IncludeFile('Test1Bundle/Migrations/Schema/v1_1/Test1BundleMigration11.php');
        $migrations = [
            new Test1BundleMigration10(),
            new Test1BundleMigration11()
        ];
        $queries    = $this->builder->getQueries($migrations);
        $this->assertEquals(2, count($queries));

        $test1BundleMigration10Data = $queries[0];
        $this->assertEquals('Migration\v1_0\Test1BundleMigration10', $test1BundleMigration10Data['migration']);
        $this->assertEquals(
            'CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)',
            $test1BundleMigration10Data['queries'][0]
        );

        $test1BundleMigration11Data = $queries[1];
        $this->assertEquals('Migration\v1_1\Test1BundleMigration11', $test1BundleMigration11Data['migration']);
        $this->assertEquals(
            'CREATE TABLE test1table (id INT NOT NULL) DEFAULT CHARACTER SET utf8 '
            . 'COLLATE utf8_unicode_ci ENGINE = InnoDB',
            $test1BundleMigration11Data['queries'][0]
        );
        $this->assertEquals(
            'ALTER TABLE TEST ADD COLUMN test_column INT NOT NULL',
            $test1BundleMigration11Data['queries'][1]
        );
    }

    public function testWrongTableNameQuery()
    {
        $this->IncludeFile('WrongTableNameMigration.php');
        $migrations = [new WrongTableNameMigration()];
        $this->setExpectedException(
            'Doctrine\ORM\Mapping\MappingException',
            sprintf(
                'Max table name length is %s. Please correct "%s" table in "%s" migration',
                MigrationQueryBuilder::MAX_TABLE_NAME_LENGTH,
                'extra_long_table_name_bigger_than_30_chars',
                'TestPackage\src\WrongTableNameMigration'
            )
        );
        $this->builder->getQueries($migrations);

    }

    public function testWrongColumnNameQuery()
    {
        $this->includeFile('WrongColumnNameMigration.php');
        $migrations = [new WrongColumnNameMigration()];
        $this->setExpectedException(
            'Doctrine\ORM\Mapping\MappingException',
            sprintf(
                'Max column name length is %s. Please correct "%s:%s" column in "%s" migration',
                MigrationQueryBuilder::MAX_TABLE_NAME_LENGTH,
                'wrong_table',
                'extra_long_column_bigger_30_chars',
                'TestPackage\src\WrongColumnNameMigration'
            )
        );
        $this->builder->getQueries($migrations);

    }

    /**
     * @param string $filePath
     */
    protected function includeFile($filePath)
    {
        $fileName = __DIR__ . '/../Fixture/src/TestPackage/src/' . $filePath;
        $this->assertFileExists($fileName);
        include_once $fileName;
    }
}
