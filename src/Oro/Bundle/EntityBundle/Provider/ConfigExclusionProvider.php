<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Configuration\EntityConfiguration;
use Oro\Bundle\EntityBundle\Configuration\EntityConfigurationProvider;

/**
 * Provides exclude logic to filter entities and fields based on exclude rules.
 */
class ConfigExclusionProvider implements ExclusionProviderInterface
{
    /** @var EntityRuleMatcher */
    private $entityHierarchyProvider;

    /** @var EntityRuleMatcher */
    private $configProvider;

    /** @var EntityRuleMatcher|null */
    private $matcher;

    public function __construct(
        EntityHierarchyProviderInterface $entityHierarchyProvider,
        EntityConfigurationProvider $configProvider
    ) {
        $this->entityHierarchyProvider = $entityHierarchyProvider;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredEntity($className)
    {
        return $this->getMatcher()->isEntityMatched($className);
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        return $this->getMatcher()->isFieldMatched(
            $metadata->name,
            $fieldName,
            $metadata->getTypeOfField($fieldName)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        return $this->getMatcher()->isFieldMatched($metadata->name, $associationName);
    }

    /**
     * @return EntityRuleMatcher
     */
    private function getMatcher()
    {
        if (null === $this->matcher) {
            $this->matcher = new EntityRuleMatcher(
                $this->entityHierarchyProvider,
                $this->configProvider->getConfiguration(EntityConfiguration::EXCLUSIONS)
            );
        }

        return $this->matcher;
    }
}
