<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\RefreshExtendCacheMigrationQuery;
use Oro\Bundle\EntityExtendBundle\Migration\RefreshExtendConfigMigrationQuery;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigration;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtension;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateExtendConfigMigrationTest extends \PHPUnit\Framework\TestCase
{
    public function testUp()
    {
        $extendOptions = ['test'];

        $initialState = ['entities' => ['Test\Entity' => ExtendScope::STATE_UPDATE]];

        $optionsPath = realpath(__DIR__ . '/../Fixtures') . '/test_options.yml';
        $initialStatePath = realpath(__DIR__ . '/../Fixtures') . '/initial_state.yml';
        $commandExecutor = $this->createMock(CommandExecutor::class);

        $migration = new UpdateExtendConfigMigration(
            $commandExecutor,
            $optionsPath,
            $initialStatePath
        );

        $schema = $this->createMock(ExtendSchema::class);
        $queries = $this->createMock(QueryBag::class);

        $dataStorageExtension = $this->createMock(DataStorageExtension::class);
        $dataStorageExtension->expects($this->once())
            ->method('get')
            ->with('initial_entity_config_state', [])
            ->willReturn($initialState);
        $migration->setDataStorageExtension($dataStorageExtension);

        $schema->expects($this->once())
            ->method('getExtendOptions')
            ->willReturn($extendOptions);

        $queries->expects($this->exactly(3))
            ->method('addQuery')
            ->withConsecutive(
                [new UpdateExtendConfigMigrationQuery($extendOptions, $commandExecutor, $optionsPath)],
                [new RefreshExtendConfigMigrationQuery($commandExecutor, $initialState, $initialStatePath)],
                [new RefreshExtendCacheMigrationQuery($commandExecutor)]
            );

        $migration->up($schema, $queries);
    }
}
