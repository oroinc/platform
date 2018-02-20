<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class RefreshExtendCacheMigrationQuery implements MigrationQuery
{
    /** @var CommandExecutor */
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
        return 'Refresh extend entity cache';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->commandExecutor->runCommand(
            'oro:entity-extend:cache:clear',
            [],
            $logger
        );
    }
}
