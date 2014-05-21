<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

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

    public function __construct(
        EntityHierarchyProvider $entityHierarchyProvider,
        $excludeRules
    ) {
        $this->entityHierarchyProvider = $entityHierarchyProvider;
        $this->excludeRules = $excludeRules;
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
        $isExcludeEntity      = !$rule['field'] && $className === $rule['entity'];
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
