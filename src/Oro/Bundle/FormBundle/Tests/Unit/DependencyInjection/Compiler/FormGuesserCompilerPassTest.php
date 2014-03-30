<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\FormBundle\DependencyInjection\Compiler\FormGuesserCompilerPass;

class FormGuesserCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessNoDefinition()
    {
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
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
        $guesserTags = array(
            'third.guesser' => array(array()),
            'first.guesser' => array(array('priority' => 20)),
            'second.guesser' => array(array('priority' => 10)),
        );
        $expectedGuessers = array(
            'first.guesser',
            'second.guesser',
            'third.guesser'
        );

        $formExtension = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();
        $formExtension->expects($this->once())
            ->method('replaceArgument')
            ->with(3, $expectedGuessers);

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
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
