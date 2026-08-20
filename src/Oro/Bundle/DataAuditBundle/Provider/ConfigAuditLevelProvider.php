<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

/**
 * Knows the system configuration levels recorded by Data Audit: which levels the application has, how each
 * of them maps to the virtual entity type stored in the audit, to its label and to its System Configuration
 * tree, and which entity the id of its scope refers to.
 *
 * The levels are the configuration scopes registered in the application, so a package that adds a scope is
 * audited without declaring anything. Its entity --- the one the scope id refers to --- is declared through
 * the "configuration_level_entities" option of the oro_data_audit configuration. See
 * {@see \Oro\Bundle\DataAuditBundle\DependencyInjection\CompilerPass\ConfigurationLevelPass}.
 */
class ConfigAuditLevelProvider
{
    /** namespace and suffix shared by every virtual configuration class */
    private const string CLASS_NAMESPACE = 'Oro\Bundle\ConfigBundle\\';
    private const string CLASS_SUFFIX = 'Configuration';

    private const string TREE_SUFFIX = '_configuration';
    private const string LABEL_PREFIX = 'oro.dataaudit.config.type.';

    /** The global scope is named "system" everywhere it is visible, and its class is already in the audit. */
    private const string GLOBAL_SCOPE = 'global';
    private const string GLOBAL_NAME = 'system';

    /** [virtual object class => scope] */
    private ?array $scopes = null;

    /**
     * @param array<string, string|null> $levels [configuration scope => entity of its scope id, if any]
     */
    public function __construct(
        private readonly array $levels
    ) {
    }

    public function getClassForScope(string $scope): string
    {
        $name = self::GLOBAL_SCOPE === $scope ? self::GLOBAL_NAME : $scope;

        return self::CLASS_NAMESPACE
            . str_replace(' ', '', ucwords(str_replace('_', ' ', $name)))
            . self::CLASS_SUFFIX;
    }

    public function isConfigType(?string $objectClass): bool
    {
        if (null === $objectClass) {
            return false;
        }

        // Both a level of this application and a level whose package has been removed since are recognised.
        return isset($this->getScopes()[$objectClass])
            || (str_starts_with($objectClass, self::CLASS_NAMESPACE)
                && str_ends_with($objectClass, self::CLASS_SUFFIX));
    }

    public function getLabelKey(string $objectClass): ?string
    {
        $scope = $this->getScopes()[$objectClass] ?? null;

        return $scope
            ? self::LABEL_PREFIX . (self::GLOBAL_SCOPE === $scope ? self::GLOBAL_NAME : $scope)
            : null;
    }

    /**
     * A readable entity type label for a level the application does not have (any more), for example
     * "Configuration: Foo Bar".
     */
    public function getGenericLabel(string $objectClass): string
    {
        $shortName = substr($objectClass, strrpos($objectClass, '\\') + 1);
        if (str_ends_with($shortName, self::CLASS_SUFFIX)) {
            $shortName = substr($shortName, 0, -\strlen(self::CLASS_SUFFIX));
        }
        $words = trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $shortName));

        return 'Configuration: ' . ('' !== $words ? $words : $shortName);
    }

    /**
     * The System Configuration tree the settings of the level live in.
     */
    public function getTreeForClass(string $objectClass): string
    {
        $scope = $this->getScopes()[$objectClass] ?? self::GLOBAL_SCOPE;

        return (self::GLOBAL_SCOPE === $scope ? self::GLOBAL_NAME : $scope) . self::TREE_SUFFIX;
    }

    /**
     * The entity whose id the scope carries, or null when the scope has no entity, declares none, or does
     * not exist in this application.
     */
    public function getTargetEntityForScope(string $scope): ?string
    {
        return ($this->levels[$scope] ?? null) ?: null;
    }

    /**
     * @return array<string, string> [virtual object class => translation key of its label]
     */
    public function all(): array
    {
        $result = [];
        foreach ($this->getScopes() as $objectClass => $scope) {
            $result[$objectClass] = $this->getLabelKey($objectClass);
        }

        return $result;
    }

    /**
     * @return array<string, string> [virtual object class => scope] of every level of this application
     */
    private function getScopes(): array
    {
        if (null === $this->scopes) {
            $scopes = [];
            foreach (array_keys($this->levels) as $scope) {
                $scopes[$this->getClassForScope($scope)] = $scope;
            }
            $this->scopes = $scopes;
        }

        return $this->scopes;
    }
}
