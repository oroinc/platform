<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityExtendBundle\Migration\ExtendMigrationExecutor;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\Migrations\Schema\v1_0\Test1BundleMigration10;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\AbstractTestMigrationExecutor;

class ExtendMigrationExecutorTest extends AbstractTestMigrationExecutor
{
    /** @var ExtendMigrationExecutor */
    protected $executor;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    public function setUp()
    {
        parent::setUp();

        /** @var ExtendOptionsManager $extendOptionManager */
        $extendOptionManager = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->nameGenerator = new ExtendDbIdentifierNameGenerator();

        $this->executor = new ExtendMigrationExecutor($this->queryExecutor);
        $this->executor->setLogger($this->logger);
        $this->executor->setNameGenerator($this->nameGenerator);
        $this->executor->setExtendOptionsManager($extendOptionManager);
    }

    public function testExtendMigrationExecutor()
    {
        $this->assertObjectHasAttribute('extendOptionsManager', $this->executor);
        $this->assertAttributeInstanceOf(
            'Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager',
            'extendOptionsManager',
            $this->executor
        );

        $migration = new Test1BundleMigration10();
        $migrations = [
            $migration
        ];

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with('CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)');

        $this->executor->executeUp($migrations);
        $messages = $this->logger->getMessages();
        $this->assertEquals(
            [
                '> ' . get_class($migration),
                'CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)',
            ],
            $messages
        );
    }
}
