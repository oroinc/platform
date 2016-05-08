<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\ChainExclusionProvider as BaseExclusionProvider;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityRuleMatcher;

class ChainExclusionProvider extends BaseExclusionProvider
{
    /** @var EntityRuleMatcher */
    protected $matcher;

    /**
     * @param EntityHierarchyProviderInterface $entityHierarchyProvider
     * @param array                            $includeRules
     */
    public function __construct(
        EntityHierarchyProviderInterface $entityHierarchyProvider,
        $includeRules
    ) {
        $this->matcher = new EntityRuleMatcher($entityHierarchyProvider, $includeRules);
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredEntity($className)
    {
        if ($this->matcher->isMatched($this->getEntityProperties($className))) {
            return false;
        }

        return parent::isIgnoredEntity($className);
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        if ($this->matcher->isMatched($this->getFieldProperties($metadata, $fieldName))) {
            return false;
        }

        return parent::isIgnoredField($metadata, $fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        if ($this->matcher->isMatched($this->getFieldProperties($metadata, $associationName))) {
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
            'entity' => $metadata->name,
            'field'  => $fieldName
        ];
    }
}
