<?php
namespace Oro\Component\Messaging\Transport\Null;

use Oro\Component\Messaging\Transport\Topic;

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