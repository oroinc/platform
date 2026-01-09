<?php

namespace Oro\Bundle\ScopeBundle\Form\DataTransformer;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Form data transformer for scope.
 */
class ScopeTransformer implements DataTransformerInterface
{
    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @var string
     */
    protected $scopeType;

    /**
     * @param ScopeManager $scopeManager
     * @param string $scopeType
     */
    public function __construct(ScopeManager $scopeManager, $scopeType)
    {
        $this->scopeManager = $scopeManager;
        $this->scopeType = $scopeType;
    }

    #[\Override]
    public function transform($value): mixed
    {
        if (null === $value) {
            return null;
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $result = [];
        $scopeFields = array_keys($this->scopeManager->getScopeEntities($this->scopeType));
        foreach ($scopeFields as $field) {
            $result[$field] = $accessor->getValue($value, $field);
        }

        return $result;
    }

    #[\Override]
    public function reverseTransform($value): mixed
    {
        if (!$value) {
            return null;
        }

        return $this->scopeManager->findOrCreate($this->scopeType, $value, false);
    }
}
