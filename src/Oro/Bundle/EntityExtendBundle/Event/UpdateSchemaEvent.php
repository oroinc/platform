<?php

namespace Oro\Bundle\EntityExtendBundle\Event;

use Psr\Log\LoggerInterface;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;

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

    /**
     * @param CommandExecutor $commandExecutor
     * @param LoggerInterface $logger
     */
    public function __construct(CommandExecutor $commandExecutor, LoggerInterface $logger)
    {
        $this->commandExecutor = $commandExecutor;
        $this->logger = $logger;
    }

    /**
     * Launches a command as a separate process.
     * @see Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor::runCommand
     *
     * @param string $command
     * @param array  $options
     *
     * @return integer The exit status code
     */
    protected function executeCommand($command, array $options)
    {
        return $this->commandExecutor->runCommand($command, $options, $this->logger);
    }
}
