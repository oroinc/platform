<?php
namespace Oro\Component\Messaging\ZeroConfig;

class Route
{
    /**
     * @var string
     */
    protected $topicName;

    /**
     * @var string
     */
    protected $processorName;

    /**
     * @var string
     */
    protected $queueName;

    /**
     * @return string
     */
    public function getTopicName()
    {
        return $this->topicName;
    }

    /**
     * @param string $topicName
     */
    public function setTopicName($topicName)
    {
        $this->topicName = $topicName;
    }

    /**
     * @return string
     */
    public function getProcessorName()
    {
        return $this->processorName;
    }

    /**
     * @param string $processorName
     */
    public function setProcessorName($processorName)
    {
        $this->processorName = $processorName;
    }

    /**
     * @return string
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * @param string $queueName
     */
    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;
    }
}
