<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OverrideServiceSwiftMailer implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        /* @var $definition \Symfony\Component\DependencyInjection\DefinitionDecorator */
        $definition = $container->findDefinition('mailer');
        $definition->addArgument(new Reference('oro_email.logger.link'));
    }
}
