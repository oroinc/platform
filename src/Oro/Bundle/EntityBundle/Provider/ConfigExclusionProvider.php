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
    private EntityHierarchyProviderInterface $entityHierarchyProvider;
    private EntityConfigurationProvider $configProvider;
    private ?EntityRuleMatcher $matcher = null;

    public function __construct(
        EntityHierarchyProviderInterface $entityHierarchyProvider,
        EntityConfigurationProvider $configProvider
    ) {
        $this->entityHierarchyProvider = $entityHierarchyProvider;
        $this->configProvider = $configProvider;
    }

    #[\Override]
    public function isIgnoredEntity($className)
    {
        return $this->getMatcher()->isEntityMatched($className);
    }

    #[\Override]
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        return $this->getMatcher()->isFieldMatched(
            $metadata->name,
            $fieldName,
            $metadata->getTypeOfField($fieldName)
        );
    }

    #[\Override]
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        return $this->getMatcher()->isFieldMatched($metadata->name, $associationName);
    }

    private function getMatcher(): EntityRuleMatcher
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
