<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all error completers.
 */
class ErrorCompleterCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        DependencyInjectionUtil::registerRequestTypeDependedTaggedServices(
            $container,
            'oro_api.error_completer_registry',
            'oro.api.error_completer'
        );
    }
}
