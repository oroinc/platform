<?php

namespace Oro\Bundle\EntityBundle\Provider;

class EntityRuleMatcher
{
    /** @var EntityHierarchyProviderInterface */
    protected $entityHierarchyProvider;

    /** @var array */
    protected $rules = [];

    /**
     * @param EntityHierarchyProviderInterface $entityHierarchyProvider
     * @param array                            $rules
     */
    public function __construct(
        EntityHierarchyProviderInterface $entityHierarchyProvider,
        $rules
    ) {
        $this->entityHierarchyProvider = $entityHierarchyProvider;
        $this->rules = $rules;
    }

    /**
     * Checks if the object (entity or field) with the given properties matches at least one rule
     *
     * @param array $objectProperties
     *
     * @return bool
     */
    public function isMatched($objectProperties)
    {
        $result = false;
        foreach ($this->rules as $rule) {
            if ($this->isRuleMatched($rule, $objectProperties)) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * Checks if the object (entity or field) with the given properties matches the given rule
     *
     * @param array $rule
     * @param array $objectProperties
     *
     * @return bool
     */
    protected function isRuleMatched($rule, $objectProperties)
    {
        $matchCount = 0;
        foreach ($rule as $key => $val) {
            if (isset($objectProperties[$key])) {
                if ($key === 'entity') {
                    // special case to compare entity class names
                    if ($this->isEntityMatched($objectProperties[$key], $val)) {
                        $matchCount++;
                    }
                } elseif ($objectProperties[$key] === $val) {
                    $matchCount++;
                }
            }
        }

        return count($rule) === $matchCount;
    }

    /**
     * Checks if $entityClassName is equal to $className
     * or has $className as one of its parent entities/mapped superclasses
     *
     * @param string $entityClassName
     * @param string $className
     *
     * @return bool
     */
    protected function isEntityMatched($entityClassName, $className)
    {
        if ($entityClassName === $className) {
            return true;
        }

        return in_array(
            $className,
            $this->entityHierarchyProvider->getHierarchyForClassName($entityClassName),
            true
        );
    }
}
