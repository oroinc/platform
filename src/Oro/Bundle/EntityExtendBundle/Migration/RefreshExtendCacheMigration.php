<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * The migration to refresh extend cache.
 */
class RefreshExtendCacheMigration implements Migration, DataStorageExtensionAwareInterface
{
    use DataStorageExtensionAwareTrait;

    /** @var ConfigManager */
    protected $configManager;

    /** @var CommandExecutor */
    protected $commandExecutor;

    /** @var string */
    protected $initialEntityConfigStatePath;

    /**
     * @param CommandExecutor $commandExecutor
     * @param ConfigManager   $configManager
     * @param string          $initialEntityConfigStatePath
     */
    public function __construct(
        CommandExecutor $commandExecutor,
        ConfigManager $configManager,
        $initialEntityConfigStatePath
    ) {
        $this->commandExecutor              = $commandExecutor;
        $this->configManager                = $configManager;
        $this->initialEntityConfigStatePath = $initialEntityConfigStatePath;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->configManager->flushAllCaches();

        if ($schema instanceof ExtendSchema) {
            $queries->addQuery(
                new RefreshExtendConfigMigrationQuery(
                    $this->commandExecutor,
                    $this->dataStorageExtension->get('initial_entity_config_state', []),
                    $this->initialEntityConfigStatePath
                )
            );
            $queries->addQuery(
                new RefreshExtendCacheMigrationQuery(
                    $this->commandExecutor
                )
            );
        }
    }
}
