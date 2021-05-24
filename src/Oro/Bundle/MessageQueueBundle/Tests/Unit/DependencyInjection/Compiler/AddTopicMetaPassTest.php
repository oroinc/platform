<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddTopicMetaPassTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedByCreateFactoryMethod()
    {
        $compiler = AddTopicMetaPass::create();

        $this->assertInstanceOf(AddTopicMetaPass::class, $compiler);
    }

    public function testShouldReturnSelfOnAdd()
    {
        $compiler = AddTopicMetaPass::create();

        $this->assertSame($compiler, $compiler->add('aTopic'));
    }

    public function testShouldDoNothingIfContainerDoesNotHaveRegistryService()
    {
        $container = new ContainerBuilder();

        $compiler = AddTopicMetaPass::create()
            ->add('fooTopic')
            ->add('barTopic');

        $compiler->process($container);
    }

    public function testShouldAddTopicsInRegistryKeepingPreviouslyAdded()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.meta.topic_meta_registry')
            ->addArgument(['bazTopic' => []]);

        $compiler = AddTopicMetaPass::create()
            ->add('fooTopic')
            ->add('barTopic');
        $compiler->process($container);

        $this->assertSame(
            [
                'bazTopic' => [],
                'fooTopic' => [],
                'barTopic' => [],
            ],
            $registryDef->getArgument(0)
        );
    }
}
