<?php

namespace Oro\Bundle\UIBundle;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\ContentProviderPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\FormattersPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\GroupingWidgetProviderPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\ReplaceTwigEnvironmentPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroUIBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new GroupingWidgetProviderPass(
            'oro_ui.widget_provider.view_actions',
            'oro_ui.view_action_provider',
            ActivityScope::VIEW_PAGE
        ));
        $container->addCompilerPass(new GroupingWidgetProviderPass(
            'oro_ui.widget_provider.update_actions',
            'oro_ui.update_action_provider',
            ActivityScope::UPDATE_PAGE
        ));
        $container->addCompilerPass(new ContentProviderPass());
        $container->addCompilerPass(new ReplaceTwigEnvironmentPass());
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
        $container->addCompilerPass(new FormattersPass());
    }
}
