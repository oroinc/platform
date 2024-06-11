<?php

namespace Oro\Bundle\ScopeBundle\Twig;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to determine if the entity scope is empty:
 *   - oro_scope_is_empty
 *   - oro_scope_entities
 */
class ScopeExtension extends AbstractExtension
{
    private PropertyAccessorInterface $propertyAccessor;

    private ScopeManager|null $scopeManager = null;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    public function setScopeManager(?ScopeManager $scopeManager): void
    {
        $this->scopeManager = $scopeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_scope_is_empty', [$this, 'isScopesEmpty']),
            new TwigFunction('oro_scope_entities', [$this, 'getScopeEntities']),
        ];
    }

    public function isScopesEmpty(array $scopeEntities, Collection $scopes): bool
    {
        if ($scopes->count() > 1) {
            return false;
        }

        $scope = $scopes->first();
        foreach ($scopeEntities as $fieldName => $class) {
            if (!empty($this->propertyAccessor->getValue($scope, $fieldName))) {
                return false;
            }
        }

        return true;
    }

    public function getScopeEntities(string $scopeType): array
    {
        return (array) $this->scopeManager?->getScopeEntities($scopeType);
    }
}
