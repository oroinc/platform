<?php
declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Processes a message-queue with a specific processor.
 */
class ConsumeMessagesCommand extends Command
{
    use LimitsExtensionsCommandTrait;

    /** @var string */
    protected static $defaultName = 'oro:message-queue:transport:consume';

    protected QueueConsumer $queueConsumer;

    public function __construct(QueueConsumer $queueConsumer)
    {
        parent::__construct();

        $this->queueConsumer = $queueConsumer;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this->configureLimitsExtensions();

        $this
            ->addArgument('queue', InputArgument::REQUIRED, 'Queue to consume from')
            ->addArgument('processor-service', InputArgument::OPTIONAL, 'The message processor service id')
            ->setDescription('Processes messages from the specified queue with the specified processor.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command consumes message from a specified message queue.
The message processor service id can be specified as the second argument.

  <info>php %command.full_name% <queue-name> <message-processor-service-id></info>

HELP
            )
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queueName = $input->getArgument('queue');
        $messageProcessorName = (string) $input->getArgument('processor-service');

        $extensions = $this->getLimitsExtensions($input, $output);
        array_unshift($extensions, $this->getLoggerExtension($input, $output));

        $this->queueConsumer->bind($queueName, $messageProcessorName);
        $this->consume($this->queueConsumer, $this->getConsumerExtension($extensions));

        return self::SUCCESS;
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
