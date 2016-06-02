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
    use LimitsExtensionsCommandTrait;

    /**
     * @var QueueConsumer
     */
    protected $consumer;

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
        $this->configureLimitsExtensions();

        $this
            ->setName('oro:message-queue:transport:consume')
            ->setDescription('A worker that consumes message from a broker. '.
                'To use this broker you have to explicitly set a queue to consume from '.
                'and a message processor service')
            ->addArgument('queue', InputArgument::REQUIRED, 'Queues to consume from')
            ->addArgument('processor-service', InputArgument::REQUIRED, 'A message processor service')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
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

        $runtimeExtensions = new Extensions($this->getLimitsExtensions($input, $output));

        try {
            $this->consumer->bind($queueName, $messageProcessor);
            $this->consumer->consume($runtimeExtensions);
        } finally {
            $this->consumer->getConnection()->close();
        }
    }
}
