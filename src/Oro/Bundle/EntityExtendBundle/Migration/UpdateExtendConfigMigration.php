<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateExtendConfigMigration implements Migration, DataStorageExtensionAwareInterface
{
    /** @var CommandExecutor */
    protected $commandExecutor;

    /** @var string */
    protected $configProcessorOptionsPath;

    /** @var string */
    protected $initialEntityConfigStatePath;

    /** @var DataStorageExtension */
    protected $dataStorageExtension;

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

    /**
     * {@inheritdoc}
     */
    public function setDataStorageExtension(DataStorageExtension $dataStorageExtension)
    {
        $this->dataStorageExtension = $dataStorageExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema instanceof ExtendSchema) {
            $queries->addQuery(
                new UpdateExtendConfigMigrationQuery(
                    $schema->getExtendOptions(),
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
            $queries->addQuery(
                new RefreshExtendCacheMigrationQuery(
                    $this->commandExecutor
                )
            );
        }
    }
}
