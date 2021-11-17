<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Defines 'transportsDsns' abstract argument for 'oro_email.mailer.transports.lazy' service.
 */
class LazyTransportsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('mailer.transports')) {
            return;
        }

        $mailerTransportsDef = $container->getDefinition('mailer.transports');
        $transportsDsns = $mailerTransportsDef->getArgument(0);

        $container
            ->getDefinition('oro_email.mailer.transports.lazy')
            ->setArgument('$transportsDsns', $transportsDsns);
    }
}
