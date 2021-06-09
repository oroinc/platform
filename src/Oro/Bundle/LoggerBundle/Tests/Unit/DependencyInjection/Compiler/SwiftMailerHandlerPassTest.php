<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\SwiftMailerHandlerPass;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SwiftMailerHandlerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var SwiftMailerHandlerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new SwiftMailerHandlerPass();
    }

    public function testProcessDoesntExecuteWhenMailersNotFound()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container->setParameter(
            'swiftmailer.mailers',
            [
                'foo' => 'foo.mailer',
                'bar' => 'bar.mailer'
            ]
        );

        $fooMailerDef = $container->register('foo.mailer');
        $barMailerDef = $container->register('bar.mailer');

        $this->compiler->process($container);

        self::assertEquals(
            new ChildDefinition('swiftmailer.plugin.no_recipient.abstract'),
            $container->getDefinition('swiftmailer.mailer.foo.plugin.no_recipient')
        );
        self::assertEquals(
            [
                ['registerPlugin', [new Reference('swiftmailer.mailer.foo.plugin.no_recipient')]]
            ],
            $fooMailerDef->getMethodCalls()
        );

        self::assertEquals(
            new ChildDefinition('swiftmailer.plugin.no_recipient.abstract'),
            $container->getDefinition('swiftmailer.mailer.bar.plugin.no_recipient')
        );
        self::assertEquals(
            [
                ['registerPlugin', [new Reference('swiftmailer.mailer.bar.plugin.no_recipient')]]
            ],
            $barMailerDef->getMethodCalls()
        );
    }
}
