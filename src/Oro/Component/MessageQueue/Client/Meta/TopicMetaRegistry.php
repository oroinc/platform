<?php
namespace Oro\Component\MessageQueue\Client\Meta;

class TopicMetaRegistry
{
    /**
     * @var array
     */
    protected $topicsMeta;

    /**
     * @param array $topicsMeta
     */
    public function __construct(array $topicsMeta)
    {
        $this->topicsMeta = $topicsMeta;
    }

    /**
     * @param string $name
     *
     * @return TopicMeta
     */
    public function getTopicMeta($name)
    {
        if (false == array_key_exists($name, $this->topicsMeta)) {
            throw new \InvalidArgumentException(sprintf('The topic meta not found. Requested name `%s`', $name));
        }

        $topic = array_replace([
            'description' => '',
            'subscribers' => [],
        ], $this->topicsMeta[$name]);

        return new TopicMeta($name, $topic['description'], $topic['subscribers']);
    }

    /**
     * @return \Generator|TopicMeta[]
     */
    public function getTopicsMeta()
    {
        foreach (array_keys($this->topicsMeta) as $name) {
            yield $this->getTopicMeta($name);
        }
    }
}
