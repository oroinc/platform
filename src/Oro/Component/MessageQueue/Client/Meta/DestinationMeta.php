<?php
namespace Oro\Component\MessageQueue\Client\Meta;

class DestinationMeta
{
    /**
     * @var string
     */
    private $clientName;

    /**
     * @var string
     */
    private $transportName;

    /**
     * @var string[]
     */
    private $subscribers;

    /**
     * @param string $clientName
     * @param string $transportName
     * @param string[] $subscribers
     */
    public function __construct($clientName, $transportName, array $subscribers = [])
    {
        $this->clientName = $clientName;
        $this->transportName = $transportName;
        $this->subscribers = $subscribers;
    }

    /**
     * @return string
     */
    public function getClientName()
    {
        return $this->clientName;
    }

    /**
     * @return string
     */
    public function getTransportName()
    {
        return $this->transportName;
    }

    /**
     * @return string[]
     */
    public function getSubscribers()
    {
        return $this->subscribers;
    }
}
