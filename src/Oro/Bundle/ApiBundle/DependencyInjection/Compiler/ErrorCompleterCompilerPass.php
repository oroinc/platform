<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;

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
        // find error completers
        $errorCompleters = [];
        $taggedServices = $container->findTaggedServiceIds(self::ERROR_COMPLETER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $definition = DependencyInjectionUtil::findDefinition($container, $id);
            if (!$definition->isPublic()) {
                throw new LogicException(
                    sprintf('The error completer service "%s" should be public.', $id)
                );
            }
            foreach ($attributes as $tagAttributes) {
                $errorCompleters[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    $id,
                    DependencyInjectionUtil::getAttribute($tagAttributes, 'requestType', null)
                ];
            }
        }
        if (empty($errorCompleters)) {
            return;
        }

        // sort by priority and flatten
        $errorCompleters = DependencyInjectionUtil::sortByPriorityAndFlatten($errorCompleters);

        // register
        $container->getDefinition(self::ERROR_COMPLETER_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $errorCompleters);
    }
}
