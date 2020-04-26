<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityExtendBundle\Migration\ExtendMigrationExecutor;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\MigrationState;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\Migrations\Schema\v1_0\Test1BundleMigration10;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\AbstractTestMigrationExecutor;

class ExtendMigrationExecutorTest extends AbstractTestMigrationExecutor
{
    public function testExtendMigrationExecutor()
    {
        /** @var ExtendOptionsManager $extendOptionManager */
        $extendOptionManager = $this->getMockBuilder(ExtendOptionsManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $executor = new class($this->queryExecutor, $this->cacheManager) extends ExtendMigrationExecutor {
            public function xgetExtendOptionsManager(): ExtendOptionsManager
            {
                return $this->extendOptionsManager;
            }
        };
        $executor->setLogger($this->logger);
        $executor->setNameGenerator(new ExtendDbIdentifierNameGenerator());

        $executor->setExtendOptionsManager($extendOptionManager);
        static::assertSame($extendOptionManager, $executor->xgetExtendOptionsManager());

        $migration = new Test1BundleMigration10();
        $migrations = [
            new MigrationState($migration)
        ];

        $this->connection->expects(static::once())
            ->method('executeQuery')
            ->with('CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)');

        $executor->executeUp($migrations);
        $messages = $this->logger->getMessages();
        static::assertEquals(
            [
                '> ' . \get_class($migration),
                'CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)',
            ],
            $messages
        );
    }
}
