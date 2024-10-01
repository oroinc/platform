<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\InstallerBundle\CommandExecutor as InstallerCommandExecutor;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\ResetContainerMigration;

/**
 * The migration to update extend configuration.
 */
class UpdateExtendConfigMigration implements Migration, ResetContainerMigration, DataStorageExtensionAwareInterface
{
    use DataStorageExtensionAwareTrait;

    /** @var CommandExecutor */
    protected $commandExecutor;

    /** @var string */
    protected $configProcessorOptionsPath;

    /** @var string */
    protected $initialEntityConfigStatePath;

    /**
     * @param CommandExecutor $commandExecutor
     * @param string          $configProcessorOptionsPath
     * @param string          $initialEntityConfigStatePath
     */
    public function __construct(
        CommandExecutor $commandExecutor,
        $configProcessorOptionsPath,
        $initialEntityConfigStatePath
    ) {
        $this->commandExecutor              = $commandExecutor;
        $this->configProcessorOptionsPath   = $configProcessorOptionsPath;
        $this->initialEntityConfigStatePath = $initialEntityConfigStatePath;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $extendOptions = $schema instanceof ExtendSchema ? $schema->getExtendOptions() : [];
        if (count($extendOptions)) {
            $queries->addQuery(
                new UpdateExtendConfigMigrationQuery(
                    $extendOptions,
                    $this->commandExecutor,
                    $this->configProcessorOptionsPath
                )
            );
            $queries->addQuery(
                new RefreshExtendConfigMigrationQuery(
                    $this->commandExecutor,
                    $this->dataStorageExtension->get('initial_entity_config_state', []),
                    $this->initialEntityConfigStatePath
                )
            );
            if (!InstallerCommandExecutor::isCommandRunning('oro:platform:update')) {
                $queries->addQuery(
                    new RefreshExtendCacheMigrationQuery(
                        $this->commandExecutor
                    )
                );
            }
        }
    }
}
