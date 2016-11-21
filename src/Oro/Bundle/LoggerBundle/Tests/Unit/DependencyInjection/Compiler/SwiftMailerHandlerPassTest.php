<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\SwiftMailerHandlerPass;

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
        $containerBuilder = new ContainerBuilder();
        $mailers = [
            'foo' => 'fooMailer',
            'bar' => 'barMailer'
        ];
        $containerBuilder->setParameter('swiftmailer.mailers', $mailers);

        $this->compilerPass->process($containerBuilder);

        $this->assertTrue($containerBuilder->hasDefinition('swiftmailer.mailer.foo.plugin.no_recipient'));
        $this->assertEquals(
            ["swiftmailer.mailer.foo.plugin.no_recipient"],
            array_keys($containerBuilder->findTaggedServiceIds('swiftmailer.foo.plugin'))
        );

        $this->assertTrue($containerBuilder->hasDefinition('swiftmailer.mailer.bar.plugin.no_recipient'));
        $this->assertEquals(
            ["swiftmailer.mailer.bar.plugin.no_recipient"],
            array_keys($containerBuilder->findTaggedServiceIds('swiftmailer.bar.plugin'))
        );
    }
}
