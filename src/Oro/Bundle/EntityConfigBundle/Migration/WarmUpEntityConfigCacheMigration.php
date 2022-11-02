<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\ResetContainerMigration;

/**
 * Warm up entity configs cache
 */
class WarmUpEntityConfigCacheMigration implements Migration, ResetContainerMigration
{
    /**
     * @var CommandExecutor
     */
    protected $commandExecutor;

    public function __construct(CommandExecutor $commandExecutor)
    {
        $this->commandExecutor = $commandExecutor;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new WarmUpEntityConfigCacheMigrationQuery($this->commandExecutor)
        );
    }
}
