<?php

namespace Oro\Bundle\LoggerBundle\Tests\Functional\Stub;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command used to test logging to the custom channel
 */
class CustomLogChannelCommandStub extends ContainerAwareCommand
{
    const LOGGER_NAME = 'monolog.logger.custom_channel';
    const LOG_MESSAGE = 'Test log message';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:logger:use-custom-channel');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getLogger()->info(self::LOG_MESSAGE);
    }

    /**
     * @return Logger
     */
    private function getLogger()
    {
        return $this->getContainer()->get(self::LOGGER_NAME);
    }
}
