<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;

class ConstantsPass implements CompilerPassInterface
{
    protected $parameters = [
        'oro_ui.widget_provider.view_actions.page_type' => ActivityScope::VIEW_PAGE,
        'oro_ui.widget_provider.update_actions.page_type' => ActivityScope::UPDATE_PAGE,
    ];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($this->parameters as $key => $value) {
            $container->setParameter($key, $value);
        }
    }
}
