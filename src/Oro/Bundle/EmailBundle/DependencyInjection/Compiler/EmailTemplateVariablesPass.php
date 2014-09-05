<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Doctrine\Common\Inflector\Inflector;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EmailTemplateVariablesPass implements CompilerPassInterface
{
    const SERVICE_KEY = 'oro_email.emailtemplate.variable_provider';
    const TAG         = 'oro_email.emailtemplate.variable_provider';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_KEY)) {
            return;
        }

        // find providers
        $providers      = [];
        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        foreach ($taggedServices as $id => $attributes) {
            if (empty($attributes[0]['scope'])) {
                throw new InvalidConfigurationException(
                    sprintf('Tag attribute "scope" is required for "%s" service', $id)
                );
            }

            $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $scope    = $attributes[0]['scope'];

            $providers[$scope][$priority][] = new Reference($id);
        }
        if (empty($providers)) {
            return;
        }

        // add to chain
        $serviceDef = $container->getDefinition(self::SERVICE_KEY);
        foreach ($providers as $scope => $items) {
            // sort by priority and flatten
            krsort($items);
            $items = call_user_func_array('array_merge', $items);
            // register
            foreach ($items as $provider) {
                $serviceDef->addMethodCall(
                    sprintf('add%sVariablesProvider', Inflector::classify($scope)),
                    [$provider]
                );
            }
        }
    }
}
