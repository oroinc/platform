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
    private const ERROR_COMPLETER_REGISTRY_SERVICE_ID = 'oro_api.error_completer_registry';
    private const ERROR_COMPLETER_TAG                 = 'oro.api.error_completer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $errorCompleters = [];
        $taggedServices = $container->findTaggedServiceIds(self::ERROR_COMPLETER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $container->getDefinition($id)->setPublic(true);
            foreach ($attributes as $tagAttributes) {
                $errorCompleters[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    $id,
                    DependencyInjectionUtil::getRequestType($tagAttributes)
                ];
            }
        }
        if (empty($errorCompleters)) {
            return;
        }

        $errorCompleters = DependencyInjectionUtil::sortByPriorityAndFlatten($errorCompleters);

        $container->getDefinition(self::ERROR_COMPLETER_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $errorCompleters);
    }
}
