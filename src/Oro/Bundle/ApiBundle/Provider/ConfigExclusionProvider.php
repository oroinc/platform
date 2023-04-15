<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Provider\ChainExclusionProvider;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityRuleMatcher;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

/**
 * The exclusion provider for entities and fields excluded from API
 * via "Resources/config/oro/api.yml" files.
 */
class ConfigExclusionProvider extends ChainExclusionProvider
{
    private EntityHierarchyProviderInterface $entityHierarchyProvider;
    private ConfigCache $configCache;
    private ExclusionProviderInterface $systemConfigExclusionProvider;
    private ?EntityRuleMatcher $excludeMatcher = null;
    private ?IncludeEntityRuleMatcher $includeMatcher = null;
    private array $entityCache = [];
    private array $fieldCache = [];

    public function __construct(
        EntityHierarchyProviderInterface $entityHierarchyProvider,
        ConfigCache $configCache,
        ExclusionProviderInterface $systemConfigExclusionProvider
    ) {
        parent::__construct();
        $this->entityHierarchyProvider = $entityHierarchyProvider;
        $this->configCache = $configCache;
        $this->systemConfigExclusionProvider = $systemConfigExclusionProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function isIgnoredEntity($className)
    {
        if (isset($this->entityCache[$className])) {
            return $this->entityCache[$className];
        }

        $result = false;
        if (!$this->getIncludeMatcher()->isEntityMatched($className)) {
            if ($this->getExcludeMatcher()->isEntityMatched($className)) {
                $result = true;
            } else {
                $result = parent::isIgnoredEntity($className);
            }
        }

        $this->entityCache[$className] = $result;

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        if (isset($this->fieldCache[$metadata->name][$fieldName])) {
            return $this->fieldCache[$metadata->name][$fieldName];
        }

        $result = false;
        if (!$this->getIncludeMatcher()->isFieldMatched($metadata->name, $fieldName)) {
            if ($this->getExcludeMatcher()->isFieldMatched($metadata->name, $fieldName)) {
                $result = true;
            } elseif ($this->getIncludeMatcher()->isEntityMatched($metadata->name)) {
                $result = parent::isIgnoredField($metadata, $fieldName);
            } else {
                $result = $this->systemConfigExclusionProvider->isIgnoredField($metadata, $fieldName)
                    || parent::isIgnoredField($metadata, $fieldName);
            }
        }

        $this->fieldCache[$metadata->name][$fieldName] = $result;

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        if (isset($this->fieldCache[$metadata->name][$associationName])) {
            return $this->fieldCache[$metadata->name][$associationName];
        }

        $result = false;
        if (!$this->getIncludeMatcher()->isFieldMatched($metadata->name, $associationName)) {
            if ($this->getExcludeMatcher()->isFieldMatched($metadata->name, $associationName)) {
                $result = true;
            } elseif ($this->getIncludeMatcher()->isEntityMatched($metadata->name)) {
                $result = parent::isIgnoredRelation($metadata, $associationName);
            } else {
                $result = $this->systemConfigExclusionProvider->isIgnoredField($metadata, $associationName)
                    || parent::isIgnoredRelation($metadata, $associationName);
            }
        }

        $this->fieldCache[$metadata->name][$associationName] = $result;

        return $result;
    }

    private function getExcludeMatcher(): EntityRuleMatcher
    {
        if (null === $this->excludeMatcher) {
            $this->excludeMatcher = new EntityRuleMatcher(
                $this->entityHierarchyProvider,
                $this->configCache->getExclusions()
            );
        }

        return $this->excludeMatcher;
    }

    private function getIncludeMatcher(): IncludeEntityRuleMatcher
    {
        if (null === $this->includeMatcher) {
            $this->includeMatcher = new IncludeEntityRuleMatcher(
                $this->entityHierarchyProvider,
                $this->configCache->getInclusions()
            );
        }

        return $this->includeMatcher;
    }
}
