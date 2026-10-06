<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

/**
 * The system configuration levels recorded by Data Audit: the configuration scopes of the application, the
 * System Configuration tree the settings of each of them live in, and the entity a scope ID refers to.
 * A scope declares that entity through the "configuration_level_entities" option of the oro_data_audit
 * configuration, see {@see \Oro\Bundle\DataAuditBundle\DependencyInjection\CompilerPass\ConfigurationLevelPass}.
 */
class ConfigAuditLevelProvider extends AbstractAuditLevelProvider
{
    private const string TREE_SUFFIX = '_configuration';

    private const string GLOBAL_SCOPE = 'global';
    private const string GLOBAL_LEVEL = 'system';

    /**
     * @param array<string, string|null> $scopes [configuration scope => entity of its scope ID, if any]
     */
    public function __construct(
        private readonly array $scopes
    ) {
        parent::__construct(
            'Oro\Bundle\ConfigBundle\\',
            'Configuration',
            'oro.dataaudit.config.type.',
            'Configuration'
        );
    }

    public function getClassForScope(string $scope): string
    {
        return $this->getClassForLevel($this->getLevel($scope));
    }

    public function getTreeForClass(string $objectClass): string
    {
        return ($this->all()[$objectClass] ?? self::GLOBAL_LEVEL) . self::TREE_SUFFIX;
    }

    public function getTargetEntityForScope(string $scope): ?string
    {
        return ($this->scopes[$scope] ?? null) ?: null;
    }

    #[\Override]
    protected function getLevels(): array
    {
        return array_map($this->getLevel(...), array_keys($this->scopes));
    }

    private function getLevel(string $scope): string
    {
        return self::GLOBAL_SCOPE === $scope ? self::GLOBAL_LEVEL : $scope;
    }
}
