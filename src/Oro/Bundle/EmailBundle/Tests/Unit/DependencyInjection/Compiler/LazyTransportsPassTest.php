<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\LazyTransportsPass;
use Oro\Bundle\EmailBundle\Mailer\Transport\LazyTransports;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\Compiler\ResolveNamedArgumentsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Mailer\Transport\Transports;

class LazyTransportsPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessDoesNothingWhenNoMailerTransports(): void
    {
        $containerBuilder = new ContainerBuilder();
        $lazyTransportsDef = new Definition(
            LazyTransports::class,
            [new Reference('sample_ref'), new AbstractArgument()]
        );
        $containerBuilder->setDefinition('oro_email.mailer.transports.lazy', $lazyTransportsDef);

        (new LazyTransportsPass())->process($containerBuilder);
        (new ResolveNamedArgumentsPass())->process($containerBuilder);

        self::assertEquals(
            $lazyTransportsDef,
            $containerBuilder->getDefinition('oro_email.mailer.transports.lazy')
        );
    }

    public function testProcessSetsFirstArgument(): void
    {
        $containerBuilder = new ContainerBuilder();
        $lazyTransportsDef = new Definition(
            LazyTransports::class,
            [new Reference('sample_ref'), new AbstractArgument()]
        );
        $transports = ['main' => 'null://null'];
        $mailerTransportsDef = new Definition(Transports::class, [$transports]);
        $containerBuilder->setDefinition('oro_email.mailer.transports.lazy', $lazyTransportsDef);
        $containerBuilder->setDefinition('mailer.transports', $mailerTransportsDef);

        (new LazyTransportsPass())->process($containerBuilder);
        // Resolves $transportsDsns named argument to the corresponding index based on class constructor.
        (new ResolveNamedArgumentsPass())->process($containerBuilder);

        self::assertEquals(
            new Definition(LazyTransports::class, [new Reference('sample_ref'), $transports]),
            $containerBuilder->getDefinition('oro_email.mailer.transports.lazy')
        );
    }
}
