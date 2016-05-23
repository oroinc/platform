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
    public function send($topic, $message, $priority = MessagePriority::NORMAL)
    {
        $this->messageProducer->send($topic, $message, $priority);

        $this->traces[] = ['topic' => $topic, 'message' => $message, 'priority' => $priority];
    }

    /**
     * @return array
     */
    public function getTraces()
    {
        return $this->traces;
    }

    /**
     * @param string $topic
     *
     * @return array
     */
    public function getTopicTraces($topic)
    {
        $topicTraces = [];
        foreach ($this->getTraces() as $trace) {
            if ($topic == $trace['topic']) {
                $topicTraces[] = $trace;
            }
        }
        
        return $topicTraces;
    }

    public function clearTraces()
    {
        $this->traces = [];
    }
}
