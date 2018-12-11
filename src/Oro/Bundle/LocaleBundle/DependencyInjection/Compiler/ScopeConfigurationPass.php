<?php

namespace Oro\Bundle\LocaleBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers preferred scope values for localization listener.
 */
class ScopeConfigurationPass implements CompilerPassInterface
{
    const SCOPE_MANAGER_TAG_NAME = 'oro_config.scope';
    const LOCALIZATION_CHANGE_LISTENER_SERVICE_ID = 'oro_locale_config.event_listener.localization_change';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds(self::SCOPE_MANAGER_TAG_NAME);
        $systemConfigSubscriber = $container->getDefinition(self::LOCALIZATION_CHANGE_LISTENER_SERVICE_ID);

        foreach ($taggedServices as $id => $attributes) {
            if (!array_key_exists('scope', $attributes[0])) {
                throw new LogicException(
                    sprintf(
                        'Tag "%s" for service "%s" must have attribute "scope".',
                        self::SCOPE_MANAGER_TAG_NAME,
                        $id
                    )
                );
            }
            $scope = $attributes[0]['scope'];
            $managerId = 'oro_config.' . $scope;
            $systemConfigSubscriber->addMethodCall('addConfigManager', [$scope, new Reference($managerId)]);
        }
    }
}
