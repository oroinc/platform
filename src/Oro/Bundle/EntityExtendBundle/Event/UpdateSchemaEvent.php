<?php

namespace Oro\Bundle\EntityExtendBundle\Event;

use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Allows to do things after the database schema is changed.
 */
class UpdateSchemaEvent extends Event
{
    const NAME = 'oro.entity_extend.entity.schema.update';

    /**
     * @var CommandExecutor
     */
    protected $commandExecutor;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(CommandExecutor $commandExecutor, LoggerInterface $logger)
    {
        $this->commandExecutor = $commandExecutor;
        $this->logger = $logger;
    }

    /**
     * Launches a command as a separate process.
     * @see \Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor::runCommand
     *
     * @param string $command
     * @param array  $options
     *
     * @return int The exit status code
     */
    protected function executeCommand($command, array $options)
    {
        return $this->commandExecutor->runCommand($command, $options, $this->logger);
    }
}
