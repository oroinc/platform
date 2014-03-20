<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Platforms\MySqlPlatform;

class AbstractTestMigrationExecutor extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $connection;

    public function setUp()
    {
        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

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
