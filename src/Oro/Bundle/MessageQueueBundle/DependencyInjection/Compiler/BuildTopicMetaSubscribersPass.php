<?php
namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BuildTopicMetaSubscribersPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processorTagName = 'oro_message_queue.zero_config.message_processor';

        $topicsSubscribers = [];
        foreach ($container->findTaggedServiceIds($processorTagName) as $serviceId => $tagAttributes) {
            foreach ($tagAttributes as $tagAttribute) {
                if (false == isset($tagAttribute['topicName'])) {
                    continue;
                }

                $topicName = $tagAttribute['topicName'];
                if (false == isset($topicsSubscribers[$topicName])) {
                    $topicsSubscribers[$topicName] = [];
                }

                $topicsSubscribers[$topicName][] = empty($tagAttribute['processorName']) ?
                    $serviceId :
                    $tagAttribute['processorName']
                ;
            }
        }

        $addTopicMetaPass = AddTopicMetaPass::create();
        foreach ($topicsSubscribers as $topicName => $subscribers) {
            $addTopicMetaPass->add($topicName, '', $subscribers);
        }

        $addTopicMetaPass->process($container);
    }
}
