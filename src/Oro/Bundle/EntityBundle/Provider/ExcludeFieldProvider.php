<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

/**
 * Provide exclude logic to filter fields based on entity exclude rules
 *
 * @package Oro\Bundle\EntityBundle
 */
class ExcludeFieldProvider
{
    /** @var EntityHierarchyProvider */
    protected $entityHierarchyProvider;

    /** @var array */
    protected $excludeRules = [];

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    public function __construct(
        EntityHierarchyProvider $entityHierarchyProvider,
        $excludeRules,
        EntityClassResolver $entityClassResolver
    ) {
        $this->entityHierarchyProvider = $entityHierarchyProvider;
        $this->excludeRules            = $excludeRules;
        $this->entityClassResolver     = $entityClassResolver;
    }

    /**
     * @param string $entityName
     * @param string $expectedClassName
     *
     * @return bool
     */
    protected function isEntityMatched($entityName, $expectedClassName)
    {
        $expectedClassName = $this->entityClassResolver->getEntityClass($expectedClassName);
        $parents = $this->entityHierarchyProvider->getHierarchyForClassName($entityName);

        if (in_array($expectedClassName, $parents)) {
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
    public function isRuleApplied($rule, $className, $fieldName, $fieldType)
    {
        $isExcludeEntity      = !$rule['field'] && $this->isEntityMatched($className, $rule['entity']);

        $isExcludeEntityField = $className === $rule['entity']
            && $rule['field'] === $fieldName;

        $isExcludeByType      = $fieldType === $rule['type'];

        if ($isExcludeEntity || $isExcludeEntityField || $isExcludeByType) {
            return true;
        }

        return false;
    }

    /**
     * @param ClassMetadata $metadata
     * @param               $fieldName
     * @param array         $extraRules
     *
     * @return bool
     */
    public function isIgnoreField(ClassMetadata $metadata, $fieldName, $extraRules = [])
    {
        $excludeRules = array_merge($this->excludeRules, $extraRules);
        $className    = $metadata->getReflectionClass()->getName();

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
    public function isIgnoredAssosiation(ClassMetadata $metadata, $associationName, $extraRules = [])
    {
        return $this->isIgnoreField($metadata, $associationName, $extraRules);
    }
}
