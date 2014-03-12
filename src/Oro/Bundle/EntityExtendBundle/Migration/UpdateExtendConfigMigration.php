<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;

class UpdateExtendConfigMigration implements Migration
{
    /**
     * @var CommandExecutor
     */
    protected $commandExecutor;

    /**
     * @var string
     */
    protected $configProcessorOptionsPath;

    /**
     * @param CommandExecutor $commandExecutor
     * @param string          $configProcessorOptionsPath
     */
    public function __construct(CommandExecutor $commandExecutor, $configProcessorOptionsPath)
    {
        $this->commandExecutor            = $commandExecutor;
        $this->configProcessorOptionsPath = $configProcessorOptionsPath;
    }

    /**
     * @inheritdoc
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
                new RefreshExtendCacheMigrationQuery(
                    $this->commandExecutor
                )
            );
        }
    }
}
