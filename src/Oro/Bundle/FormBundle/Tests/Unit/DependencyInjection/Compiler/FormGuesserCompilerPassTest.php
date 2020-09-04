<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\FormBundle\DependencyInjection\Compiler\FormGuesserCompilerPass;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;

class FormGuesserCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $guesserTags = [
            'third.guesser' => [['name' => 'form.extension']],
            'first.guesser' => [['name' => 'form.extension', 'priority' => 20]],
            'second.guesser' => [['name' => 'form.extension', 'priority' => 10]],
        ];

        $expectedGuessers = new IteratorArgument([
            new Reference('first.guesser'),
            new Reference('second.guesser'),
            new Reference('third.guesser')
        ]);

        $guesser1 = $this->createMock(Definition::class);
        $guesser2 = $this->createMock(Definition::class);
        $guesser3 = $this->createMock(Definition::class);
        $formExtension = $this->createMock(Definition::class);
        $formExtension->expects($this->once())
            ->method('replaceArgument')
            ->with(2, $expectedGuessers);

        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->any())
            ->method('getParameterBag')
            ->willReturn(new ParameterBag());
        $container->expects($this->any())
            ->method('getDefinition')
            ->willReturnMap([
                ['form.extension', $formExtension],
                ['first.guesser', $guesser1],
                ['second.guesser', $guesser2],
                ['third.guesser', $guesser3],
            ]);

        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('form.type_guesser')
            ->willReturn($guesserTags);

        $compiler = new FormGuesserCompilerPass();
        $compiler->process($container);
    }
}
