<?php

namespace Oro\Bundle\SearchBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\SearchBundle\DependencyInjection\Compiler\ListenerExcludeSearchConnectionPass;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;

class OroSearchBundle extends Bundle
{
    /** {@inheritdoc} */
    public function build(ContainerBuilder $container)
    {
        if ($container instanceof ExtendedContainerBuilder) {
            $container->addCompilerPass(new ListenerExcludeSearchConnectionPass());
            $container->moveCompilerPassBefore(
                'Oro\Bundle\SearchBundle\DependencyInjection\Compiler\ListenerExcludeSearchConnectionPass',
                'Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UpdateDoctrineEventHandlersPass'
            );
        }
    }
}
