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
    protected $consumerName;

    public function __construct()
    {
        $this->exclusive = false;
    }

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
     * @return boolean
     */
    public function isExclusive()
    {
        return $this->exclusive;
    }

    /**
     * @param boolean $exclusive
     */
    public function setExclusive($exclusive)
    {
        $this->exclusive = (bool) $exclusive;
    }

    /**
     * @return string
     */
    public function getConsumerName()
    {
        return $this->consumerName;
    }

    /**
     * @param string $consumerName
     */
    public function setConsumerName($consumerName)
    {
        $this->consumerName = $consumerName;
    }
}
