<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityExtendBundle\Migration\ExtendMigrationExecutor;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\MigrationState;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\Migrations\Schema\v1_0\Test1BundleMigration10;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\AbstractTestMigrationExecutor;
use Oro\Component\Testing\ReflectionUtil;

class ExtendMigrationExecutorTest extends AbstractTestMigrationExecutor
{
    public function testExtendMigrationExecutor()
    {
        $extendOptionManager = $this->createMock(ExtendOptionsManager::class);

        $executor = new ExtendMigrationExecutor($this->queryExecutor, $this->cacheManager);
        $executor->setLogger($this->logger);
        $executor->setNameGenerator(new ExtendDbIdentifierNameGenerator());

        $executor->setExtendOptionsManager($extendOptionManager);
        self::assertSame($extendOptionManager, ReflectionUtil::getPropertyValue($executor, 'extendOptionsManager'));

        $migration = new Test1BundleMigration10();
        $migrations = [
            new MigrationState($migration)
        ];

        $this->connection->expects(self::once())
            ->method('executeQuery')
            ->with('CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)');

        $executor->executeUp($migrations);
        $messages = $this->logger->getMessages();
        self::assertEquals(
            [
                '> ' . \get_class($migration),
                'CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)',
            ],
            $messages
        );
    }
}
