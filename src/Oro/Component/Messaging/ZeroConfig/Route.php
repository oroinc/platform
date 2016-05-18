<?php
namespace Oro\Component\Messaging\ZeroConfig;

class Route
{
    /**
     * @var string
     */
    protected $messageName;

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
    public function getMessageName()
    {
        return $this->messageName;
    }

    /**
     * @param string $messageName
     */
    public function setMessageName($messageName)
    {
        $this->messageName = $messageName;
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
