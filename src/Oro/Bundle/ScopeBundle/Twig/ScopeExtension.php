<?php

namespace Oro\Bundle\ScopeBundle\Twig;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * This extension provides some scope related helper functions for twig.
 */
class ScopeExtension extends \Twig_Extension
{
    const NAME = 'oro_scope';

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @param PropertyAccessorInterface $propertyAccessor
     */
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
            new \Twig_SimpleFunction('oro_scope_is_empty', [$this, 'isScopesEmpty'])
        ];
    }

    /**
     * @param array $scopeEntities
     * @param Collection $scopes
     * @return bool
     */
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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
