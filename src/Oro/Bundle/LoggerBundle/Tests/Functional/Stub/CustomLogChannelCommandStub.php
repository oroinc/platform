<?php

namespace Oro\Bundle\LoggerBundle\Tests\Functional\Stub;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command used to test logging to the custom channel
 */
#[AsCommand(name: 'oro:logger:use-custom-channel')]
class CustomLogChannelCommandStub extends Command
{
    public const LOGGER_NAME = 'monolog.logger.custom_channel';
    public const LOG_MESSAGE = 'Test log message';

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->getLogger()->info(self::LOG_MESSAGE);

        return Command::SUCCESS;
    }

    /**
     * @return Logger
     */
    private function getLogger()
    {
        return $this->logger;
    }
}
