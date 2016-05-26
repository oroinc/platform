<?php
namespace Oro\Component\MessageQueue\Transport\Null;

use Oro\Component\MessageQueue\Transport\Queue;

class NullQueue implements Queue
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getQueueName()
    {
        return $this->name;
    }
}
