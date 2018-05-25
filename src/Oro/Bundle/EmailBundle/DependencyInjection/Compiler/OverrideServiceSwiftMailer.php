<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Updates mailer definition
 */
class OverrideServiceSwiftMailer implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        /* @var $definition \Symfony\Component\DependencyInjection\ChildDefinition */
        $definition = $container->findDefinition('mailer');
        $definition->addArgument(new Reference('oro_email.logger.link'));
    }
}
