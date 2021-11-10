<?php
declare(strict_types=1);

namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\LimitsExtensionsCommandTrait;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Processes messages from the message-queue.
 */
class ConsumeMessagesCommand extends Command
{
    use LimitsExtensionsCommandTrait;

    /** @var string */
    protected static $defaultName = 'oro:message-queue:consume';

    protected QueueConsumer $queueConsumer;

    protected DestinationMetaRegistry $destinationMetaRegistry;

    public function __construct(
        QueueConsumer $queueConsumer,
        DestinationMetaRegistry $destinationMetaRegistry
    ) {
        parent::__construct();

        $this->queueConsumer = $queueConsumer;
        $this->destinationMetaRegistry = $destinationMetaRegistry;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addArgument('clientDestinationName', InputArgument::OPTIONAL, 'Queues to consume messages from')
            ->setDescription('Processes messages from the message-queue.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command processes messages from the message-queue
using an appropriate message processor based on message headers.

  <info>php %command.full_name%</info>

It connects to the default queue, but a different name can be provided as the argument: 

  <info>php %command.full_name% <clientDestinationName></info>

HELP
            )
        ;

        $this->configureLimitsExtensions();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clientDestinationName = $input->getArgument('clientDestinationName');
        if ($clientDestinationName) {
            $this->queueConsumer->bind(
                $this->destinationMetaRegistry->getDestinationMeta($clientDestinationName)->getTransportQueueName()
            );
        } else {
            foreach ($this->destinationMetaRegistry->getDestinationsMeta() as $destinationMeta) {
                $this->queueConsumer->bind($destinationMeta->getTransportQueueName());
            }
        }

        $extensions = $this->getLimitsExtensions($input, $output);
        array_unshift($extensions, $this->getLoggerExtension($input, $output));

        $this->consume($this->queueConsumer, $this->getConsumerExtension($extensions));

        return 0;
    }

    protected function consume(QueueConsumer $consumer, ExtensionInterface $extension): void
    {
        try {
            $consumer->consume($extension);
        } finally {
            $consumer->getConnection()->close();
        }
    }

    protected function getConsumerExtension(array $extensions): ExtensionInterface
    {
        return new ChainExtension($extensions);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpUnusedParameterInspection
     */
    protected function getLoggerExtension(InputInterface $input, OutputInterface $output): ExtensionInterface
    {
        return new LoggerExtension(new ConsoleLogger($output));
    }
}
