<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Migration\v1_0\Test1BundleMigration10;

use Oro\Bundle\EntityExtendBundle\Migration\ExtendMigrationQueryLoader;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\AbstractTestMigrationQueryLoader;

class ExtendMigrationQueryLoaderTest extends AbstractTestMigrationQueryLoader
{
    public function setUp()
    {
        parent::setUp();

        /** @var ExtendOptionsManager $extendOptionManager */
        $extendOptionManager = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new ExtendMigrationQueryLoader($this->connection);

        $this->nameGenerator = new ExtendDbIdentifierNameGenerator();
        $this->builder->setNameGenerator($this->nameGenerator);
        $this->builder->setExtendOptionsManager($extendOptionManager);
    }

    public function testExtendMigrationQueryLoader()
    {
        $this->assertObjectHasAttribute('extendOptionsManager', $this->builder);
        $this->assertAttributeInstanceOf(
            'Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager',
            'extendOptionsManager',
            $this->builder
        );

        $this->IncludeFile('Test1Bundle/Migrations/Schema/v1_0/Test1BundleMigration10.php');
        $migrations = [
            new Test1BundleMigration10()
        ];
        $queries = $this->builder->getQueries($migrations);
        $this->assertEquals(1, count($queries));

        $test1BundleMigration10Data = $queries[0];
        $this->assertEquals('Migration\v1_0\Test1BundleMigration10', $test1BundleMigration10Data['migration']);
        $this->assertEquals(
            'CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)',
            $test1BundleMigration10Data['queries'][0]
        );
    }
}
