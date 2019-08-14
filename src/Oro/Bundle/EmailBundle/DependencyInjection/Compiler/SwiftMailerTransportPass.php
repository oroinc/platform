<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Makes transports public.
 */
class SwiftMailerTransportPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $mailers = array_keys($container->getParameter('swiftmailer.mailers'));
        foreach ($mailers as $name) {
            $container->getAlias(sprintf('swiftmailer.mailer.%s.transport.real', $name))
                ->setPublic(true);
        }
    }
}
