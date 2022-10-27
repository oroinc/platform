<?php

namespace Oro\Bundle\LoggerBundle\Tests\Functional\Stub;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command used to test logging to the custom channel
 */
class CustomLogChannelCommandStub extends Command
{
    public const LOGGER_NAME = 'monolog.logger.custom_channel';
    public const LOG_MESSAGE = 'Test log message';

    /** @var string */
    protected static $defaultName = 'oro:logger:use-custom-channel';

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getLogger()->info(self::LOG_MESSAGE);

        return 0;
    }

    /**
     * @return Logger
     */
    private function getLogger()
    {
        return $this->logger;
    }
}
