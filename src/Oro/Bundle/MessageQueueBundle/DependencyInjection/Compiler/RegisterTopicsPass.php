<?php
namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterTopicsPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    private $topics;

    /**
     * @param array $topics
     */
    public function __construct(array $topics)
    {
        $this->topics = $topics;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (false == $container->hasDefinition('oro_message_queue.zero_config.topic_registry')) {
            return;
        }

        $topicRegistry = $container->getDefinition('oro_message_queue.zero_config.topic_registry');

        $topicRegistry->replaceArgument(0, array_replace($topicRegistry->getArgument(0), $this->topics));
    }
}
