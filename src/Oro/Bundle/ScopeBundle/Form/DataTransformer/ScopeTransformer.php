<?php

namespace Oro\Bundle\ScopeBundle\Form\DataTransformer;

use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

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

    /**
     * {@inheritdoc}
     */
    public function transform($value)
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

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return null;
        }

        return $this->scopeManager->findOrCreate($this->scopeType, $value, false);
    }
}
