<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\LazyServicesCompilerPass;

class LazyServicesCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessLazyServicesTag()
    {
        $expectedTags = array(
            'doctrine.event_listener'
        );

        $compiler = new LazyServicesCompilerPass();
        $this->assertAttributeEquals($expectedTags, 'lazyServicesTags', $compiler);

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $containerIteration = 0;

        foreach ($expectedTags as $tagName) {
            $testTags = array(
                'first.' . $tagName => array(),
                'second.' . $tagName => array(),
            );
            $container->expects($this->at(++$containerIteration))->method('findTaggedServiceIds')->with($tagName)
                ->will($this->returnValue($testTags));

            foreach (array_keys($testTags) as $serviceId) {
                $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');
                $definition->expects($this->once())->method('setLazy')->with(true)->willReturn($definition);
                $definition->expects($this->once())->method('setPublic')->with(false)->willReturn($definition);

                $container->expects($this->at(++$containerIteration))->method('hasDefinition')->with($serviceId)
                    ->will($this->returnValue(true));
                $container->expects($this->at(++$containerIteration))->method('getDefinition')->with($serviceId)
                    ->will($this->returnValue($definition));
            }
        }

        $compiler->process($container);
    }
}
