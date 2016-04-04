<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\ChainExclusionProvider as BaseExclusionProvider;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProvider;

class ChainExclusionProvider extends BaseExclusionProvider
{
    /** @var EntityHierarchyProvider */
    protected $entityHierarchyProvider;

    /** @var array */
    protected $includeRules = [];

    /**
     * @param EntityHierarchyProvider $entityHierarchyProvider
     * @param array                   $includeRules
     */
    public function __construct(
        EntityHierarchyProvider $entityHierarchyProvider,
        $includeRules
    ) {
        $this->entityHierarchyProvider = $entityHierarchyProvider;
        $this->includeRules = $includeRules;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredEntity($className)
    {
        if ($this->isMatched($this->getEntityProperties($className))) {
            return false;
        }

        return parent::isIgnoredEntity($className);
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        if ($this->isMatched($this->getFieldProperties($metadata, $fieldName))) {
            return false;
        }

        return parent::isIgnoredField($metadata, $fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        if ($this->isMatched($this->getFieldProperties($metadata, $associationName))) {
            return false;
        }

        return parent::isIgnoredRelation($metadata, $associationName);
    }

    /**
     * Returns properties for entity object
     *
     * @param string $className
     *
     * @return array
     */
    protected function getEntityProperties($className)
    {
        return [
            'entity' => $className
        ];
    }

    /**
     * Returns properties for entity field object
     *
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     *
     * @return array
     */
    protected function getFieldProperties(ClassMetadata $metadata, $fieldName)
    {
        return [
            'entity' => $metadata->getName(),
            'field'  => $fieldName
        ];
    }

    /**
     * Checks if the object (entity or field) with the given properties matches at least one include rule
     *
     * @param array $objectProperties
     *
     * @return bool
     */
    protected function isMatched($objectProperties)
    {
        $result = false;
        foreach ($this->includeRules as $rule) {
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
