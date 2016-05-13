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
    protected $handlerName;

    /**
     * @var string
     */
    protected $consumerName;

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
    public function getHandlerName()
    {
        return $this->handlerName;
    }

    /**
     * @param string $handlerName
     */
    public function setHandlerName($handlerName)
    {
        $this->handlerName = $handlerName;
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
