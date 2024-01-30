<?php

namespace Oro\Bundle\FormBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Makes form.factory service public
 */
class PublicFormServicesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('form.factory')->setPublic(true);
    }
}
