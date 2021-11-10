<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicDescriptionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddTopicDescriptionPassTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedByCreateFactoryMethod(): void
    {
        $compiler = AddTopicDescriptionPass::create();

        self::assertInstanceOf(AddTopicDescriptionPass::class, $compiler);
    }

    public function testShouldReturnSelfOnAdd(): void
    {
        $compiler = AddTopicDescriptionPass::create();

        self::assertSame($compiler, $compiler->add('aTopic'));
    }

    public function testShouldDoNothingIfContainerDoesNotHaveRegistryService(): void
    {
        $container = new ContainerBuilder();

        $compiler = AddTopicDescriptionPass::create()
            ->add('fooTopic')
            ->add('barTopic');

        $compiler->process($container);
    }

    public function testShouldAddTopicsInRegistryKeepingPreviouslyAdded(): void
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.meta.topic_description_provider')
            ->setArgument(0, ['bazTopic' => 'baz_description']);

        $compiler = AddTopicDescriptionPass::create()
            ->add('fooTopic')
            ->add('barTopic', 'bar_description');
        $compiler->process($container);

        self::assertSame(
            [
                'bazTopic' => 'baz_description',
                'fooTopic' => '',
                'barTopic' => 'bar_description',
            ],
            $registryDef->getArgument(0)
        );
    }
}
