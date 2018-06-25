<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\FormBundle\DependencyInjection\Compiler\FormGuesserCompilerPass;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FormGuesserCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessNoDefinition()
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())
            ->method('hasDefinition')
            ->with('form.extension')
            ->will($this->returnValue(false));
        $container->expects($this->never())
            ->method('getDefinition');

        $compiler = new FormGuesserCompilerPass();
        $compiler->process($container);
    }

    public function testProcess()
    {
        $guesserTags = [
            'third.guesser' => [[]],
            'first.guesser' => [['priority' => 20]],
            'second.guesser' => [['priority' => 10]],
        ];

        $expectedGuessers = new IteratorArgument([
            new Reference('first.guesser'),
            new Reference('second.guesser'),
            new Reference('third.guesser')
        ]);

        $formExtension = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();
        $formExtension->expects($this->once())
            ->method('replaceArgument')
            ->with(2, $expectedGuessers);

        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())
            ->method('hasDefinition')
            ->with('form.extension')
            ->will($this->returnValue(true));
        $container->expects($this->once())
            ->method('getDefinition')
            ->with('form.extension')
            ->will($this->returnValue($formExtension));
        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('form.type_guesser')
            ->will($this->returnValue($guesserTags));

        $compiler = new FormGuesserCompilerPass();
        $compiler->process($container);
    }
}
