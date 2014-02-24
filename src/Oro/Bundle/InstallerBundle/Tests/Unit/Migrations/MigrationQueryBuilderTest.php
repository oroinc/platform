<?php

namespace Oro\Bundle\InstallerBundleTests\Unit\Migrations;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\MigrationQueryBuilder;

class MigrationQueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var MigrationQueryBuilder */
    protected $builder;

    protected $em;

    protected $connection;

    public function setUp()
    {
//        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
//            ->disableOriginalConstructor()
//            ->getMock();
//        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
//            ->disableOriginalConstructor()
//            ->getMock();
//        $this->em->expects($this->any())
//            ->method('getConnection')
//            ->will($this->returnValue($this->connection));
//
//        $platform = new MySqlPlatform();
//        $schema = new Schema();
//        $sm = $this->getMockForAbstractClass(
//            'Doctrine\DBAL\Schema\AbstractSchemaManager',
//            [],
//            '',
//            false
//        );
//        $sm->expects($this->once())
//            ->method('createSchema')
//            ->will($this->returnValue($schema));
//        $this->connection->expects($this->once())
//            ->method('getSchemaManager')
//            ->will($this->returnValue($sm));
//        $this->connection->expects($this->once())
//            ->method('getDatabasePlatform')
//            ->will($this->returnValue($platform));
//
//        $this->builder = new MigrationQueryBuilder($this->em);
    }

    public function testGetMigrationsQueries()
    {
        //$queries = $this->builder->getQueries([]);
        //var_dump($queries);
    }
}
