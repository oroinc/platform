<?php
namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddTopicMetaPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    private $topicsMeta;

    public function __construct()
    {
        $this->topicsMeta = [];
    }

    /**
     * @param string $topicName
     * @param string $topicDescription
     * @param array $topicSubscribers
     *
     * @return $this
     */
    public function add($topicName, $topicDescription = '', array $topicSubscribers = [])
    {
        $this->topicsMeta[$topicName] = [];
        
        if ($topicDescription) {
            $this->topicsMeta[$topicName]['description'] = $topicDescription;
        }

        if ($topicSubscribers) {
            $this->topicsMeta[$topicName]['subscribers'] = $topicSubscribers;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $registryId = 'oro_message_queue.zero_config.meta.topic_meta_registry';
        
        if (false == $container->hasDefinition($registryId)) {
            return;
        }

        $topicRegistry = $container->getDefinition($registryId);
        
        $topicRegistry->replaceArgument(0, array_merge_recursive($topicRegistry->getArgument(0), $this->topicsMeta));
    }

    /**
     * @return static
     */
    public static function create()
    {
        return new static;
    }
}
