<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;

class RefreshExtendCacheMigration implements Migration
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var CommandExecutor
     */
    protected $commandExecutor;

    /**
     * @param CommandExecutor $commandExecutor
     * @param ConfigManager $configManager
     */
    public function __construct(CommandExecutor $commandExecutor, ConfigManager $configManager)
    {
        $this->commandExecutor = $commandExecutor;
        $this->configManager = $configManager;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->configManager->clearCache();
        $this->configManager->clearConfigurableCache();

        if ($schema instanceof ExtendSchema) {
            $queries->addQuery(
                new RefreshExtendCacheMigrationQuery($this->commandExecutor)
            );
        }
    }
}
