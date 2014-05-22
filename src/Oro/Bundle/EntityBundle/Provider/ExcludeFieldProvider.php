<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Provide exclude logic to filter fields based on entity exclude rules
 */
class ExcludeFieldProvider
{
    /** @var EntityHierarchyProvider */
    protected $entityHierarchyProvider;

    /** @var array */
    protected $excludeRules = [];

    /**
     * @param EntityHierarchyProvider $entityHierarchyProvider
     * @param array                   $excludeRules
     */
    public function __construct(
        EntityHierarchyProvider $entityHierarchyProvider,
        $excludeRules
    ) {
        $this->entityHierarchyProvider = $entityHierarchyProvider;
        $this->excludeRules            = $excludeRules;
    }

    /**
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     * @param array         $extraRules
     *
     * @return bool
     */
    public function isIgnoreField(ClassMetadata $metadata, $fieldName, $extraRules = [])
    {
        $excludeRules = array_merge($this->excludeRules, $extraRules);
        $className    = $metadata->getName();

        foreach ($excludeRules as $rule) {
            $fieldType = $metadata->getTypeOfField($fieldName);

            if ($this->isRuleApplied($rule, $className, $fieldName, $fieldType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ClassMetadata $metadata
     * @param string        $associationName
     * @param array         $extraRules
     *
     * @return bool
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName, $extraRules = [])
    {
        return $this->isIgnoreField($metadata, $associationName, $extraRules);
    }

    /**
     * @param string $entityName
     * @param string $expectedClassName
     *
     * @return bool
     */
    protected function isEntityMatched($entityName, $expectedClassName)
    {
        $parents = $this->entityHierarchyProvider->getHierarchyForClassName($entityName);

        if ($expectedClassName === $entityName || in_array($expectedClassName, $parents)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $rule
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     *
     * @return bool
     */
    protected function isRuleApplied($rule, $className, $fieldName, $fieldType)
    {
        $isEntityMatched      = isset($rule['entity']) ? $this->isEntityMatched($className, $rule['entity']) : false;
        $isExcludeEntity      = !isset($rule['field']) && $isEntityMatched;
        $isExcludeEntityField = $isEntityMatched && isset($rule['field']) && $rule['field'] === $fieldName;
        $isExcludeByType      = isset($rule['type']) && $fieldType === $rule['type'];

        if ($isExcludeEntity || $isExcludeEntityField || $isExcludeByType) {
            return true;
        }

        return false;
    }
}
