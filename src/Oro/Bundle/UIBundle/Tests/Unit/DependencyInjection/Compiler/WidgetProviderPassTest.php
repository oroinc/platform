<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\UIBundle\DependencyInjection\Compiler\WidgetProviderPass;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class WidgetProviderPassTest extends \PHPUnit\Framework\TestCase
{
    private const SERVICE_ID = 'test_service';
    private const TAG_NAME   = 'test_tag';

    public function testProcess()
    {
        $container = new ContainerBuilder();

        $service = $container->setDefinition(self::SERVICE_ID, new Definition(\stdClass::class, [[]]));

        $container->setDefinition('tagged_service_1', new Definition())
            ->addTag(self::TAG_NAME, ['priority' => 100]);
        $container->setDefinition('tagged_service_2', new Definition())
            ->addTag(self::TAG_NAME, ['priority' => -100]);
        $container->setDefinition('tagged_service_3', new Definition())
            ->addTag(self::TAG_NAME);
        $container->setDefinition('tagged_service_4', new Definition())
            ->addTag(self::TAG_NAME, ['priority' => 100]);

        $compiler = new WidgetProviderPass(self::SERVICE_ID, self::TAG_NAME);
        $compiler->process($container);

        /** @var IteratorArgument $iterator */
        $iterator = $service->getArgument(0);
        $this->assertInstanceOf(IteratorArgument::class, $iterator);
        $this->assertEquals(
            [
                new Reference('tagged_service_2'),
                new Reference('tagged_service_3'),
                new Reference('tagged_service_1'),
                new Reference('tagged_service_4')
            ],
            $iterator->getValues()
        );
    }
}
