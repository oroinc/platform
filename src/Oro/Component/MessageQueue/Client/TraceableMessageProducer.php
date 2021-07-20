<?php

namespace Oro\Component\MessageQueue\Client;

/**
 * The message producer that collects sent messages.
 */
class TraceableMessageProducer implements MessageProducerInterface
{
    /** @var MessageProducerInterface */
    private $messageProducer;

    /** @var array */
    private $traces = [];

    public function __construct(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        $this->messageProducer->send($topic, $message);

        $this->traces[] = ['topic' => $topic, 'message' => $message];
    }

    /**
     * @return array
     */
    public function getTraces()
    {
        foreach ($this->traces as $key => $trace) {
            $message = $trace['message'];
            if ($message instanceof MessageBuilderInterface) {
                $this->traces[$key]['message'] = $message->getMessage();
            }
        }

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
            if ($topic === $trace['topic']) {
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
