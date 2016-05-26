<?php
namespace Oro\Component\MessageQueue\ZeroConfig;

class TopicRegistry
{
    /**
     * @var array
     */
    protected $topics;

    /**
     * @param array   $topics
     */
    public function __construct(array $topics = [])
    {
        $this->topics = $topics;
    }

    /**
     * @param string $name
     *
     * @return Topic
     */
    public function getTopic($name)
    {
        if (false == array_key_exists($name, $this->topics)) {
            throw new \InvalidArgumentException(sprintf('The topic not found. Topic name: %s', $name));
        }

        $topic = array_replace([
            'description' => '',
        ], $this->topics[$name]);

        return new Topic($name, $topic['description']);
    }

    /**
     * @return \Generator|Topic[]
     */
    public function getTopics()
    {
        foreach (array_keys($this->topics) as $name) {
            yield $this->getTopic($name);
        }
    }
}
