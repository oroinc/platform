<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class WarmUpEntityConfigCacheMigrationQuery implements MigrationQuery
{
    /**
     * @var CommandExecutor
     */
    protected $commandExecutor;

    /**
     * @param CommandExecutor $commandExecutor
     */
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
