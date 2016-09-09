<?php
namespace Oro\Component\MessageQueue\Client\Meta;

class TopicMeta
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;
    
    /**
     * @var string[]
     */
    private $subscribers;

    /**
     * @param string $name
     * @param string $description
     * @param string[] $subscribers
     */
    public function __construct($name, $description = '', array $subscribers = [])
    {
        $this->name = $name;
        $this->description = $description;
        $this->subscribers = $subscribers;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string[]
     */
    public function getSubscribers()
    {
        return $this->subscribers;
    }
}
