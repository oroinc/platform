<?php
namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ConsumeMessagesCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var QueueConsumer
     */
    private $consumer;

    /**
     * ConsumeMessagesCommand constructor.
     * @param QueueConsumer $consumer
     */
    public function __construct(QueueConsumer $consumer)
    {
        parent::__construct($name = 'oro:message-queue:transport:consume');
        
        $this->consumer = $consumer;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('A worker that consumes message from a broker. '.
                'To use this broker you have to explicitly set a queue to consume from '.
                'and a message processor service')
            ->addArgument('queue', InputArgument::REQUIRED, 'Queues to consume from')
            ->addArgument('processor-service', InputArgument::REQUIRED, 'A message processor service')
            ->addOption('message-limit', InputOption::VALUE_REQUIRED, 'Consume n messages and exit')
            ->addOption('time-limit', InputOption::VALUE_REQUIRED, 'Consume messages during this time')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extensions = [];

        if ($messageLimit = (int) $input->getOption('message-limit')) {
            $extensions[] = new LimitConsumedMessagesExtension($messageLimit);
        }

        if ($timeLimit = $input->getOption('time-limit')) {
            try {
                $timeLimit = new \DateTime($timeLimit);
            } catch (\Exception $e) {
                $output->writeln('<error>Invalid time limit</error>');

                return;
            }

            $extensions[] = new LimitConsumptionTimeExtension($timeLimit);
        }

        $extensions[] = new LoggerExtension(new ConsoleLogger($output));

        $queueName = $input->getArgument('queue');

        /** @var MessageProcessorInterface $messageProcessor */
        $messageProcessor = $this->container->get($input->getArgument('processor-service'));
        if (false == $messageProcessor instanceof  MessageProcessorInterface) {
            throw new \LogicException(sprintf(
                'Invalid message processor service given. It must be an instance of %s but %s',
                MessageProcessorInterface::class,
                get_class($messageProcessor)
            ));
        }

        $runtimeExtensions = new Extensions([$extensions]);

        try {
            $this->consumer->consume($queueName, $messageProcessor, $runtimeExtensions);
        } finally {
            $this->consumer->getConnection()->close();
        }
    }
}
