<?php

namespace Oro\Bundle\UIBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\UIBundle\DependencyInjection\Compiler\ContentProviderPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\FormattersPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\ViewActionWidgetProviderPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\UpdateActionWidgetProviderPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\ConstantsPass;

class OroUIBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ViewActionWidgetProviderPass());
        $container->addCompilerPass(new UpdateActionWidgetProviderPass());
        $container->addCompilerPass(new ContentProviderPass());
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
        $container->addCompilerPass(new FormattersPass());
        $container->addCompilerPass(new ConstantsPass());
    }
}
