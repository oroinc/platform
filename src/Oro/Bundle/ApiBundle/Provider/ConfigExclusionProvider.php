<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\ConfigExclusionProvider as BaseExclusionProvider;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityRuleMatcher;

/**
 * The exclusion provider for entities and fields excluded from Data API
 * via "Resources/config/oro/api.yml" files.
 */
class ConfigExclusionProvider extends BaseExclusionProvider
{
    /** @var EntityRuleMatcher */
    private $includeMatcher;

    /** @var array */
    private $cache = [];

    /**
     * @param EntityHierarchyProviderInterface $entityHierarchyProvider
     * @param array                            $excludeRules
     * @param array                            $includeRules
     */
    public function __construct(
        EntityHierarchyProviderInterface $entityHierarchyProvider,
        array $excludeRules,
        array $includeRules
    ) {
        parent::__construct($entityHierarchyProvider, $excludeRules);
        $this->includeMatcher = new EntityRuleMatcher($entityHierarchyProvider, $includeRules);
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredEntity($className)
    {
        if ($this->includeMatcher->isMatched($this->getEntityProperties($className))) {
            return false;
        }

        return parent::isIgnoredEntity($className);
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        if (isset($this->cache[$metadata->name][$fieldName])) {
            return $this->cache[$metadata->name][$fieldName];
        }

        $result = false;
        if (!$this->includeMatcher->isMatched($this->getIncludeFieldProperties($metadata, $fieldName))) {
            $result = parent::isIgnoredField($metadata, $fieldName);
        }

        $this->cache[$metadata->name][$fieldName] = $result;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        if (isset($this->cache[$metadata->name][$associationName])) {
            return $this->cache[$metadata->name][$associationName];
        }

        $result = false;
        if (!$this->includeMatcher->isMatched($this->getIncludeFieldProperties($metadata, $associationName))) {
            $result = parent::isIgnoredRelation($metadata, $associationName);
        }

        $this->cache[$metadata->name][$associationName] = $result;

        return $result;
    }

    /**
     * Returns properties for entity field object for matching include rules
     *
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     *
     * @return array
     */
    private function getIncludeFieldProperties(ClassMetadata $metadata, string $fieldName): array
    {
        return [
            'entity' => $metadata->name,
            'field'  => $fieldName
        ];
    }
}
