<?php

namespace Oro\Bundle\DataAuditBundle\DependencyInjection\CompilerPass;

use Oro\Bundle\DataAuditBundle\DependencyInjection\Configuration;
use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorTrait;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Builds the list of audited system configuration levels: every configuration scope of the application,
 * together with the entity its scope id refers to.
 *
 * The scopes come from the same ``oro_config.scope`` tags the configuration itself is built from, so the
 * audit always knows exactly the levels the application has, and the entities come from the
 * "configuration_level_entities" option every bundle contributes to.
 */
class ConfigurationLevelPass implements CompilerPassInterface
{
    use PriorityTaggedLocatorTrait;

    private const string SCOPE_MANAGER_TAG_NAME = 'oro_config.scope';
    private const string LEVEL_PROVIDER_SERVICE = 'oro_dataaudit.provider.config_audit_level_provider';
    public const string ENTITIES_PARAMETER = 'oro_dataaudit.configuration_level_entities';

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $scopes = array_keys($this->findAndSortTaggedServices(
            self::SCOPE_MANAGER_TAG_NAME,
            'scope',
            $container
        ));
        $entities = $container->hasParameter(self::ENTITIES_PARAMETER)
            ? $container->getParameter(self::ENTITIES_PARAMETER)
            : [];

        $unknownScopes = array_diff(array_keys($entities), $scopes);
        if ($unknownScopes) {
            throw new InvalidConfigurationException(sprintf(
                'The "%s.configuration_level_entities" option declares the entity of "%s", but there is no'
                . ' such configuration scope. The available scopes are "%s".',
                Configuration::ROOT_NODE,
                implode('", "', $unknownScopes),
                implode('", "', $scopes)
            ));
        }

        $levels = [];
        foreach ($scopes as $scope) {
            $levels[$scope] = $entities[$scope] ?? null;
        }

        $container->getDefinition(self::LEVEL_PROVIDER_SERVICE)->replaceArgument(0, $levels);
    }
}
