<?php
namespace Oro\Component\MessageQueue\Client;

class TraceableMessageProducer implements MessageProducerInterface
{
    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @var array
     */
    protected $traces = [];

    /**
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $body, $priority = MessagePriority::NORMAL)
    {
        $this->messageProducer->send($topic, $body, $priority);

        $this->traces[$topic][] = ['body' => $body, 'priority' => $priority];
    }

    /**
     * @return array
     */
    public function getTraces()
    {
        return $this->traces;
    }

    public function clearTraces()
    {
        $this->traces = [];
    }
}
