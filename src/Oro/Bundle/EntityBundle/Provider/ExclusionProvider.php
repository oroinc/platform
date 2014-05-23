<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Provide exclude logic to filter entities and fields based on exclude rules
 */
class ExclusionProvider implements ExclusionProviderInterface
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
     * {@inheritdoc}
     */
    public function isIgnoredEntity(ClassMetadata $metadata)
    {
        return $this->isMatched($this->getEntityProperties($metadata));
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        return $this->isMatched($this->getFieldProperties($metadata, $fieldName));
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        return $this->isMatched($this->getFieldProperties($metadata, $associationName));
    }

    /**
     * Returns properties for entity object
     *
     * @param ClassMetadata $metadata
     * @return array
     */
    protected function getEntityProperties(ClassMetadata $metadata)
    {
        return [
            'entity' => $metadata->getName()
        ];
    }

    /**
     * Returns properties for entity field object
     *
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     * @return array
     */
    protected function getFieldProperties(ClassMetadata $metadata, $fieldName)
    {
        return [
            'entity' => $metadata->getName(),
            'field'  => $fieldName,
            'type'   => $metadata->getTypeOfField($fieldName)
        ];
    }

    /**
     * Checks if the object (entity or field) with the given properties matches at least one exclusion rule
     *
     * @param array $objectProperties
     * @return bool
     */
    protected function isMatched($objectProperties)
    {
        $result = false;
        foreach ($this->excludeRules as $rule) {
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
     * @return bool
     */
    protected function isEntityMatched($entityClassName, $className)
    {
        if ($entityClassName === $className) {
            return true;
        }

        return in_array(
            $className,
            $this->entityHierarchyProvider->getHierarchyForClassName($entityClassName)
        );
    }
}
