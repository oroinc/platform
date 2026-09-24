<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

/**
 * The levels a kind of menu can be customized at: the scope criteria registered for its scope type, so a
 * package that contributes a level is audited without declaring anything. One instance describes one kind
 * of menu (the back-office menus, the storefront menus, ...).
 */
class MenuAuditLevelProvider extends AbstractAuditLevelProvider
{
    public const string GLOBAL_LEVEL = 'global';

    public function __construct(
        private readonly ScopeManager $scopeManager,
        private readonly EntityNameResolver $entityNameResolver,
        private readonly string $scopeType,
        string $classPrefix,
        string $classSuffix,
        string $labelPrefix,
        string $genericLabel
    ) {
        parent::__construct($classPrefix, $classSuffix, $labelPrefix, $genericLabel);
    }

    public function getClassForScope(Scope $scope): string
    {
        return $this->getClassForLevel($this->findLevel($scope));
    }

    public function getTargetName(Scope $scope): ?string
    {
        $criteria = $this->getCriteria($scope);
        foreach ($this->getCriteriaFieldsByPriority() as $field) {
            $value = $criteria[$field] ?? null;
            if (\is_object($value)) {
                return (string)$this->entityNameResolver->getName($value) ?: null;
            }
        }

        return null;
    }

    #[\Override]
    protected function getLevels(): array
    {
        return [self::GLOBAL_LEVEL, ...$this->getCriteriaFieldsByPriority()];
    }

    /**
     * @return string[]
     */
    private function getCriteriaFieldsByPriority(): array
    {
        return array_keys($this->scopeManager->getScopeEntities($this->scopeType));
    }

    private function findLevel(Scope $scope): string
    {
        $criteria = $this->getCriteria($scope);
        foreach ($this->getCriteriaFieldsByPriority() as $field) {
            if (null !== ($criteria[$field] ?? null)) {
                return $field;
            }
        }

        return self::GLOBAL_LEVEL;
    }

    private function getCriteria(Scope $scope): array
    {
        return $this->scopeManager->getCriteriaByScope($scope, $this->scopeType)->toArray();
    }
}
