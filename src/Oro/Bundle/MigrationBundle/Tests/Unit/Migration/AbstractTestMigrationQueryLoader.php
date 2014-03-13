<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Platforms\MySqlPlatform;

use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\MigrationQueryLoaderWithNameGenerator;

class AbstractTestMigrationQueryLoader extends \PHPUnit_Framework_TestCase
{
    /** @var MigrationQueryLoaderWithNameGenerator */
    protected $builder;

    protected $em;

    protected $connection;

    /** @var DbIdentifierNameGenerator */
    protected $nameGenerator;

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
        $sm       = $this->getMockBuilder('Doctrine\DBAL\Schema\AbstractSchemaManager')
            ->disableOriginalConstructor()
            ->setMethods(['listTables', 'createSchemaConfig'])
            ->getMockForAbstractClass();
        $sm->expects($this->once())
            ->method('listTables')
            ->will($this->returnValue([]));
        $sm->expects($this->once())
            ->method('createSchemaConfig')
            ->will($this->returnValue(null));
        $this->connection->expects($this->once())
            ->method('getSchemaManager')
            ->will($this->returnValue($sm));
        $this->connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($platform));

        $this->nameGenerator = new DbIdentifierNameGenerator();

        $this->builder = new MigrationQueryLoaderWithNameGenerator($this->connection);
        $this->builder->setNameGenerator($this->nameGenerator);
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
