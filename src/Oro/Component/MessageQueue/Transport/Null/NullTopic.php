<?php
namespace Oro\Component\MessageQueue\Transport\Null;

use Oro\Component\MessageQueue\Transport\Topic;

class NullTopic implements Topic
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
    public function getTopicName()
    {
        return $this->name;
    }
}