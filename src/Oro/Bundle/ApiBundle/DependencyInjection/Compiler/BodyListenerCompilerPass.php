<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\EventListener\BodyListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Replaces {@see \FOS\RestBundle\EventListener\BodyListener} with
 * {@see \Oro\Bundle\ApiBundle\EventListener\BodyListener} to be able to return detailed info
 * if a request body contains an invalid JSON document.
 */
class BodyListenerCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('fos_rest.body_listener')
            ->setClass(BodyListener::class);
    }
}
