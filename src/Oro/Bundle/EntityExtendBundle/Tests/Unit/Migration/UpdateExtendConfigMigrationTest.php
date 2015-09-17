<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\RefreshExtendCacheMigrationQuery;
use Oro\Bundle\EntityExtendBundle\Migration\RefreshExtendConfigMigrationQuery;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigration;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigrationQuery;

class UpdateExtendConfigMigrationTest extends \PHPUnit_Framework_TestCase
{
    public function testUp()
    {
        $extendOptions = ['test'];

        $initialState = ['entities' => ['Test\Entity' => ExtendScope::STATE_UPDATE]];

        $optionsPath = realpath(__DIR__ . '/../Fixtures') . '/test_options.yml';
        $initialStatePath = realpath(__DIR__ . '/../Fixtures') . '/initial_state.yml';
        $commandExecutor = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor')
            ->disableOriginalConstructor()
            ->getMock();

        $migration = new UpdateExtendConfigMigration(
            $commandExecutor,
            $optionsPath,
            $initialStatePath
        );

        $schema = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema')
            ->disableOriginalConstructor()
            ->getMock();
        $queries = $this->getMockBuilder('Oro\Bundle\MigrationBundle\Migration\QueryBag')
            ->disableOriginalConstructor()
            ->getMock();

        $dataStorageExtension = $this
            ->getMockBuilder('Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtension')
            ->disableOriginalConstructor()
            ->getMock();
        $dataStorageExtension->expects($this->once())
            ->method('get')
            ->with('initial_entity_config_state', [])
            ->willReturn($initialState);
        $migration->setDataStorageExtension($dataStorageExtension);

        $schema->expects($this->once())
            ->method('getExtendOptions')
            ->will($this->returnValue($extendOptions));

        $queries->expects($this->at(0))
            ->method('addQuery')
            ->with(
                new UpdateExtendConfigMigrationQuery(
                    $extendOptions,
                    $commandExecutor,
                    $optionsPath
                )
            );
        $queries->expects($this->at(1))
            ->method('addQuery')
            ->with(
                new RefreshExtendConfigMigrationQuery(
                    $commandExecutor,
                    $initialState,
                    $initialStatePath
                )
            );
        $queries->expects($this->at(2))
            ->method('addQuery')
            ->with(
                new RefreshExtendCacheMigrationQuery(
                    $commandExecutor
                )
            );

        $migration->up($schema, $queries);
    }
}
