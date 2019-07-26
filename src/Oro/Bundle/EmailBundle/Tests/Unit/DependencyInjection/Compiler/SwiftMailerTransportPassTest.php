<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\SwiftMailerTransportPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SwiftMailerTransportPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container->setParameter('swiftmailer.mailers', ['test1' => null, 'test2' => null]);

        $alias1 = $container->setAlias('swiftmailer.mailer.test1.transport.real', 'service.test1')->setPublic(false);
        $alias2 = $container->setAlias('swiftmailer.mailer.test2.transport.real', 'service.test2')->setPublic(true);

        $compiler = new SwiftMailerTransportPass();
        $compiler->process($container);

        $this->assertTrue($alias1->isPublic());
        $this->assertTrue($alias2->isPublic());
    }
}
