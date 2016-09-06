<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

use Oro\Component\MessageQueue\Client\DelegateMessageProcessor;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;

class MessageConsumer
{
    /**
     * @var QueueConsumer
     */
    private $consumer;

    /**
     * @var DelegateMessageProcessor
     */
    private $processor;

    /**
     * @var DestinationMetaRegistry
     */
    private $destinationMetaRegistry;

    /**
     * @var ChainExtension
     */
    private $runtimeExtension = [];

    /**
     * @var bool
     */
    private $isInitiated = false;

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
        $this->runtimeExtension = new ChainExtension([]);
        $this->consumer = $consumer;
        $this->processor = $processor;
        $this->destinationMetaRegistry = $destinationMetaRegistry;

    }

    public function consume()
    {
        $this->init();
        try {
            $this->consumer->consume($this->runtimeExtension);
        } finally {
            $this->consumer->getConnection()->close();
        }
    }

    protected function init()
    {
        if (!$this->isInitiated) {
            foreach ($this->destinationMetaRegistry->getDestinationsMeta() as $destinationMeta) {
                $this->consumer->bind(
                    $destinationMeta->getTransportName(),
                    $this->processor
                );
            }
            $this->isInitiated = true;
        }
    }

    /**
     * @param ExtensionInterface $runtimeExtension
     */
    public function addExtension(ExtensionInterface $runtimeExtension)
    {
        $this->runtimeExtension = new ChainExtension([$this->runtimeExtension, $runtimeExtension]);
    }
}
