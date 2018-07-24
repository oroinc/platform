<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\SwiftMailerHandlerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SwiftMailerHandlerPassTest extends \PHPUnit\Framework\TestCase
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
        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder->expects($this->any())
            ->method('hasParameter')
            ->with('swiftmailer.mailers')
            ->willReturn(false);

        $containerBuilder->expects($this->never())->method('getParameter');
    }


    public function testProcess()
    {
        $containerBuilder = new ContainerBuilder();
        $mailers = [
            'foo' => 'foo.mailer',
            'bar' => 'bar.mailer'
        ];
        $containerBuilder->setParameter('swiftmailer.mailers', $mailers);

        $fooDefinition = $this->createMock(Definition::class);
        $fooDefinition->expects($this->once())
            ->method('addMethodCall')
            ->with('registerPlugin', [new Reference('swiftmailer.mailer.foo.plugin.no_recipient')]);
        $containerBuilder->setDefinition('foo.mailer', $fooDefinition);

        $barDefinition = $this->createMock(Definition::class);
        $barDefinition->expects($this->once())
            ->method('addMethodCall')
            ->with('registerPlugin', [new Reference('swiftmailer.mailer.bar.plugin.no_recipient')]);
        $containerBuilder->setDefinition('bar.mailer', $barDefinition);

        $this->compilerPass->process($containerBuilder);

        $this->assertTrue($containerBuilder->hasDefinition('swiftmailer.mailer.foo.plugin.no_recipient'));
        $this->assertTrue($containerBuilder->hasDefinition('swiftmailer.mailer.bar.plugin.no_recipient'));
    }
}
