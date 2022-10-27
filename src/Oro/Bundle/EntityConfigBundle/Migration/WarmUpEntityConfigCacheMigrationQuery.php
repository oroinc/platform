<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\ResetContainerMigration;
use Psr\Log\LoggerInterface;

/**
 * Warm up entity configs cache
 */
class WarmUpEntityConfigCacheMigrationQuery implements MigrationQuery, ResetContainerMigration
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
        return 'Warm up entity configs cache';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->commandExecutor->runCommand(
            'oro:entity-config:cache:warmup',
            [],
            $logger
        );
    }
}
