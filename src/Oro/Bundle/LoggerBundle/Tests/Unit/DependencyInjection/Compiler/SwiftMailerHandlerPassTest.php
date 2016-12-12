<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\SwiftMailerHandlerPass;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

class SwiftMailerHandlerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SwiftMailerHandlerPass
     */
    protected $compilerPass;

    protected function setUp()
    {
        $this->compilerPass = new SwiftMailerHandlerPass();
    }

    public function testProcessDoesntExecuteWhenMailersNotFound()
    {
        $containerBuilder = $this->getMock(ContainerBuilder::class, [], [], '', false);
        $containerBuilder->expects($this->any())
            ->method('hasParameter')
            ->with('swiftmailer.mailers')
            ->willReturn(false);

        $containerBuilder->expects($this->never())->method('getParameter');
    }


    public function testProcess()
    {
        $decorator = new DefinitionDecorator('swiftmailer.plugin.no_recipient.abstract');
        $containerBuilder = new ContainerBuilder();
        $mailers = [
            'foo' => 'foo.mailer',
            'bar' => 'bar.mailer'
        ];
        $containerBuilder->setParameter('swiftmailer.mailers', $mailers);

        $fooDefinition = $this->getMock(Definition::class);
        $fooDefinition->expects($this->once())
            ->method('addMethodCall')
            ->with('registerPlugin', [$decorator]);
        $containerBuilder->setDefinition('foo.mailer', $fooDefinition);

        $barDefinition = $this->getMock(Definition::class);
        $barDefinition->expects($this->once())
            ->method('addMethodCall')
            ->with('registerPlugin', [$decorator]);
        $containerBuilder->setDefinition('bar.mailer', $barDefinition);

        $this->compilerPass->process($containerBuilder);

        $this->assertTrue($containerBuilder->hasDefinition('swiftmailer.mailer.foo.plugin.no_recipient'));
        $this->assertTrue($containerBuilder->hasDefinition('swiftmailer.mailer.bar.plugin.no_recipient'));
    }
}
