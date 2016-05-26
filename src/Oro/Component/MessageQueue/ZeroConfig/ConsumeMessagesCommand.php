<?php
namespace Oro\Component\MessageQueue\ZeroConfig;

use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Consumption\Extensions;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeMessagesCommand extends Command
{
   /**
    * @var QueueConsumer
    */
    protected $consumer;

    /**
     * @var DelegateMessageProcessor
     */
    protected $processor;

    /**
     * @var Session
     */
    private $session;

    /**
     * @param QueueConsumer $consumer
     * @param DelegateMessageProcessor $processor
     * @param Session $session
     */
    public function __construct(QueueConsumer $consumer, DelegateMessageProcessor $processor, Session $session)
    {
        parent::__construct('oro:message-queue:zeroconfig:consume');

        $this->consumer = $consumer;
        $this->processor = $processor;
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('A worker that processes messages')
            ->addArgument('queue', InputArgument::OPTIONAL, 'Queues to consume from')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->session->getConfig();
        
        $loggerExtension = new LoggerExtension(new ConsoleLogger($output));
        $runtimeExtensions = new Extensions([$loggerExtension]);

        $queueName = $input->getArgument('queue')
            ? $config->formatName($input->getArgument('queue'))
            : $config->getDefaultQueueName()
        ;

        try {
            $this->consumer->consume($queueName, $this->processor, $runtimeExtensions);
        } finally {
            $this->consumer->getConnection()->close();
        }
    }
}
