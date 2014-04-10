<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LazyServicesCompilerPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    protected $lazyServices = array(
        'assetic.asset_manager',
        'knp_menu.renderer.twig',
        'templating',
        'twig',
        'templating.engine.twig',
        'twig.controller.exception',
    );

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($this->lazyServices as $id) {
            if ($container->hasDefinition($id)) {
                $container->getDefinition($id)->setLazy(true);
            }
        }
    }
}
