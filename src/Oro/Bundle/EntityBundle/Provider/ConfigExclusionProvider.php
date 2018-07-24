<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Provides exclude logic to filter entities and fields based on exclude rules.
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
        return $this->matcher->isEntityMatched($className);
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        return $this->matcher->isFieldMatched($metadata->name, $fieldName, $metadata->getTypeOfField($fieldName));
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        return $this->matcher->isFieldMatched($metadata->name, $associationName);
    }
}
