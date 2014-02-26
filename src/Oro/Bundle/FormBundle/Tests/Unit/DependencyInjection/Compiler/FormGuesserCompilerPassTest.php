<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\FormBundle\DependencyInjection\Compiler\FormGuesserCompilerPass;

class FormGuesserCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $guesserTags = array(
            'third.guesser' => array(array('priority' => 10)),
            'first.guesser' => array(array('priority' => 30)),
            'second.guesser' => array(array('priority' => 20)),
        );

        $chainGuesser = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();
        $chainGuesser->expects($this->at(0))
            ->method('addMethodCall')
            ->with('addGuesser', array(new Reference('first.guesser')));
        $chainGuesser->expects($this->at(1))
            ->method('addMethodCall')
            ->with('addGuesser', array(new Reference('second.guesser')));
        $chainGuesser->expects($this->at(2))
            ->method('addMethodCall')
            ->with('addGuesser', array(new Reference('third.guesser')));

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->once())
            ->method('getDefinition')
            ->with(FormGuesserCompilerPass::CHAIN_GUESSER)
            ->will($this->returnValue($chainGuesser));
        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(FormGuesserCompilerPass::GUESSER_TAG)
            ->will($this->returnValue($guesserTags));

        $compiler = new FormGuesserCompilerPass();
        $compiler->process($container);
    }
}
