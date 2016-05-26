<?php
namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
        parent::__construct($name = 'oro:message-queue:consume');
        
        $this->consumer = $consumer;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('A worker that consumes message from a broker')
            ->addArgument('queue', InputArgument::REQUIRED, 'Queues to consume from')
            ->addArgument('processor-service', InputArgument::REQUIRED, 'A message processor service')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loggerExtension = new LoggerExtension(new ConsoleLogger($output));

        $queueName = $input->getArgument('queue');

        /** @var MessageProcessor $messageProcessor */
        $messageProcessor = $this->container->get($input->getArgument('processor-service'));
        if (false == $messageProcessor instanceof  MessageProcessor) {
            throw new \LogicException(sprintf(
                'Invalid message processor service given. It must be an instance of %s but %s',
                MessageProcessor::class,
                get_class($messageProcessor)
            ));
        }

        $runtimeExtensions = new Extensions([$loggerExtension]);

        try {
            $this->consumer->consume($queueName, $messageProcessor, $runtimeExtensions);
        } finally {
            $this->consumer->getConnection()->close();
        }
    }
}
