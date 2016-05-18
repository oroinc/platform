<?php
namespace Oro\Component\Messaging;

use Oro\Component\Messaging\Consumption\QueueConsumer;
use Oro\Component\Messaging\ZeroConfig\Config;
use Oro\Component\Messaging\ZeroConfig\RouterMessageProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RouteMessagesCommand extends Command
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
     * @param QueueConsumer          $consumer
     * @param RouterMessageProcessor $processor
     * @param Config                 $config
     */
    public function __construct(QueueConsumer $consumer, RouterMessageProcessor $processor, Config $config)
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
            ->setName('oro:messaging:zeroconf:route-messages')
            ->setDescription('A worker that routes messages to queue')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Consuming...');

        $this->consumer->consume($this->config->getRouterQueueName(), $this->processor);

        $output->writeln('Exiting');
    }
}
