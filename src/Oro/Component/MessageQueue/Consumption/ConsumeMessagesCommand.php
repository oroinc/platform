<?php
declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Psr\Container\ContainerInterface;
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
    protected ContainerInterface $processorLocator;

    public function __construct(QueueConsumer $queueConsumer, ContainerInterface $processorLocator)
    {
        parent::__construct();

        $this->queueConsumer = $queueConsumer;
        $this->processorLocator = $processorLocator;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this->configureLimitsExtensions();

        $this
            ->addArgument('queue', InputArgument::REQUIRED, 'Queues to consume from')
            ->addArgument('processor-service', InputArgument::REQUIRED, 'A message processor service')
            ->setDescription('Processes a message-queue with a specific processor.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command consumes message from a specified message queue.
The message processor service should be specified as the second argument.

  <info>php %command.full_name% <queue> <processor-service></info>

HELP
            )
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queueName = $input->getArgument('queue');
        $messageProcessor = $this->getMessageProcessor($input->getArgument('processor-service'));

        $extensions = $this->getLimitsExtensions($input, $output);
        array_unshift($extensions, $this->getLoggerExtension($input, $output));

        $this->queueConsumer->bind($queueName, $messageProcessor);
        $this->consume($this->queueConsumer, $this->getConsumerExtension($extensions));
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

    private function getMessageProcessor(string $processorServiceId): MessageProcessorInterface
    {
        $processor = $this->processorLocator->get($processorServiceId);
        if (!$processor instanceof MessageProcessorInterface) {
            throw new \LogicException(sprintf(
                'Invalid message processor service given. It must be an instance of %s but %s',
                MessageProcessorInterface::class,
                get_class($processor)
            ));
        }

        return $processor;
    }
}
