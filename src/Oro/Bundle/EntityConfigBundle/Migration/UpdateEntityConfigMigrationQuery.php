<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\ResetContainerMigration;
use Psr\Log\LoggerInterface;

/**
 * Update entity configs
 */
class UpdateEntityConfigMigrationQuery implements MigrationQuery, ResetContainerMigration
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
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Update entity configs';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->commandExecutor->runCommand(
            'oro:entity-config:update',
            [],
            $logger
        );
    }
}
