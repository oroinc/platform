<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Provide exclude logic to filter entities and fields based on exclude rules
 */
class ConfigExclusionProvider implements ExclusionProviderInterface
{
    /** @var EntityRuleMatcher */
    protected $matcher;

    /**
     * @param EntityHierarchyProviderInterface $entityHierarchyProvider
     * @param array                            $excludeRules
     */
    public function __construct(
        EntityHierarchyProviderInterface $entityHierarchyProvider,
        $excludeRules
    ) {
        $this->matcher = new EntityRuleMatcher($entityHierarchyProvider, $excludeRules);
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredEntity($className)
    {
        return $this->matcher->isMatched($this->getEntityProperties($className));
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        return $this->matcher->isMatched($this->getFieldProperties($metadata, $fieldName));
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        return $this->matcher->isMatched($this->getFieldProperties($metadata, $associationName));
    }

    /**
     * Returns properties for entity object
     *
     * @param string $className
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
     * @return array
     */
    protected function getFieldProperties(ClassMetadata $metadata, $fieldName)
    {
        return [
            'entity' => $metadata->name,
            'field'  => $fieldName,
            'type'   => $metadata->getTypeOfField($fieldName)
        ];
    }
}
