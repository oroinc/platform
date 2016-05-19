<?php
namespace Oro\Component\Messaging\Router;

use Oro\Component\Messaging\Transport\Destination;
use Oro\Component\Messaging\Transport\Message;

class Recipient
{
    /**
     * @var Destination
     */
    private $destination;
    
    /**
     * @var Message
     */
    private $message;

    /**
     * @param Destination $destination
     * @param Message $message
     */
    public function __construct(Destination $destination, Message $message)
    {
        $this->destination = $destination;
        $this->message = $message;
    }

    /**
     * @return Destination
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }
}
