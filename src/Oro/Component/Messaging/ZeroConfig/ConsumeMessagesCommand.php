<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Consumption\QueueConsumer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeMessagesCommand extends Command
{
   /**
    * @var QueueConsumer
    */
    protected $consumer;

    /**
     * @var RouterMessageProcessor
     */
    protected $processor;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param QueueConsumer         $consumer
     * @param QueueMessageProcessor $processor
     * @param Config                $config
     */
    public function __construct(QueueConsumer $consumer, QueueMessageProcessor $processor, Config $config)
    {
        parent::__construct(null);

        $this->consumer = $consumer;
        $this->processor = $processor;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:messaging:zeroconf:consume-messages')
            ->setDescription('A worker that processes messages')
            ->addArgument('queue', InputArgument::OPTIONAL, 'Queues to consume from')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Consuming...');

        $queueName = $input->getArgument('queue') ?: $this->config->getDefaultQueueQueueName();

        $this->consumer->consume($queueName, $this->processor);

        $output->writeln('Exiting');
    }
}