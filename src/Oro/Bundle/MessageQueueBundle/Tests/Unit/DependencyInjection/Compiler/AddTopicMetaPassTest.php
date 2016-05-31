<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AddTopicMetaPassTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementCompilerPass()
    {
        $this->assertClassImplements(CompilerPassInterface::class, AddTopicMetaPass::class);
    }

    public function testCouldBeConstructedWithoutAntArguments()
    {
        new AddTopicMetaPass([]);
    }

    public function testCouldBeConstructedByCreateFactoryMethod()
    {
        $pass = AddTopicMetaPass::create();

        $this->assertInstanceOf(AddTopicMetaPass::class, $pass);
    }

    public function testShouldReturnSelfOnAdd()
    {
        $pass = AddTopicMetaPass::create();

        $this->assertSame($pass, $pass->add('aTopic'));
    }

    public function testShouldDoNothingIfContainerDoesNotHaveRegistryService()
    {
        $container = new ContainerBuilder();

        $pass = AddTopicMetaPass::create()
            ->add('fooTopic')
            ->add('barTopic')
        ;

        $pass->process($container);
    }

    public function testShouldAddTopicsInRegistryKeepingPreviouslyAdded()
    {
        $container = new ContainerBuilder();

        $registry = new Definition(null, [[
            'bazTopic' => []
        ]]);
        $container->setDefinition('oro_message_queue.client.meta.topic_meta_registry', $registry);

        $pass = AddTopicMetaPass::create()
            ->add('fooTopic')
            ->add('barTopic')
        ;
        $pass->process($container);

        $expectedTopics = [
            'bazTopic' => [],
            'fooTopic' => [],
            'barTopic' => [],
        ];

        $this->assertSame($expectedTopics, $registry->getArgument(0));
    }
}
