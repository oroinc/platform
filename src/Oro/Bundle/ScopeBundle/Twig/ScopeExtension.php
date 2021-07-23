<?php

namespace Oro\Bundle\ScopeBundle\Twig;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to determine if the entity scope is empty:
 *   - oro_scope_is_empty
 */
class ScopeExtension extends AbstractExtension
{
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_scope_is_empty', [$this, 'isScopesEmpty'])
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
}
