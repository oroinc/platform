<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestActivity;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

class TestConfigExclusionProvider implements ExclusionProviderInterface
{
    private ExclusionProviderInterface $innerExclusionProvider;

    public function __construct(ExclusionProviderInterface $innerExclusionProvider)
    {
        $this->innerExclusionProvider = $innerExclusionProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function isIgnoredEntity($className)
    {
        if (TestActivity::class === $className) {
            return true;
        }

        return $this->innerExclusionProvider->isIgnoredEntity($className);
    }

    /**
     * {@inheritDoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        return $this->innerExclusionProvider->isIgnoredField($metadata, $fieldName);
    }

    /**
     * {@inheritDoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        return $this->innerExclusionProvider->isIgnoredRelation($metadata, $associationName);
    }
}
