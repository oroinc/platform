<?php
namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\LimitsExtensionsCommandTrait;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeMessagesCommand extends Command
{
    use LimitsExtensionsCommandTrait;

   /**
    * @var QueueConsumer
    */
    protected $consumer;

    /**
     * @var DelegateMessageProcessor
     */
    protected $processor;

    /**
     * @var DestinationMetaRegistry
     */
    private $destinationMetaRegistry;

    /**
     * @param QueueConsumer $consumer
     * @param DelegateMessageProcessor $processor
     * @param DestinationMetaRegistry $destinationMetaRegistry
     */
    public function __construct(
        QueueConsumer $consumer,
        DelegateMessageProcessor $processor,
        DestinationMetaRegistry $destinationMetaRegistry
    ) {
        parent::__construct('oro:message-queue:consume');

        $this->consumer = $consumer;
        $this->processor = $processor;
        $this->destinationMetaRegistry = $destinationMetaRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->configureLimitsExtensions();

        $this
            ->setDescription('A client\'s worker that processes messages. '.
                'By default it connects to default queue. '.
                'It select an appropriate message processor based on a message headers')
            ->addArgument('clientDestinationName', InputArgument::OPTIONAL, 'Queues to consume messages from')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($clientDestinationName = $input->getArgument('clientDestinationName')) {
            $this->consumer->bind(
                $this->destinationMetaRegistry->getDestinationMeta($clientDestinationName)->getTransportName(),
                $this->processor
            );
        } else {
            foreach ($this->destinationMetaRegistry->getDestinationsMeta() as $destinationMeta) {
                $this->consumer->bind(
                    $destinationMeta->getTransportName(),
                    $this->processor
                );
            }
        }

        $runtimeExtensions = new ChainExtension($this->getLimitsExtensions($input, $output));

        try {
            $this->consumer->consume($runtimeExtensions);
        } finally {
            $this->consumer->getConnection()->close();
        }
    }
}
