<?php
namespace Oro\Component\MessageQueue\Router;

use Oro\Component\MessageQueue\Transport\DestinationInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;

class Recipient
{
    /**
     * @var DestinationInterface
     */
    private $destination;
    
    /**
     * @var MessageInterface
     */
    private $message;

    /**
     * @param DestinationInterface $destination
     * @param MessageInterface $message
     */
    public function __construct(DestinationInterface $destination, MessageInterface $message)
    {
        $this->destination = $destination;
        $this->message = $message;
    }

    /**
     * @return DestinationInterface
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @return MessageInterface
     */
    public function getMessage()
    {
        return $this->message;
    }
}
