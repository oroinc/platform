<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Functional\Stub;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * The decorator for FeatureChecker that allows to configure the feature checker in functional tests.
 */
class FeatureCheckerStub extends FeatureChecker
{
    private array $resourceEnabled = [];

    public function setResourceEnabled(string $resource, string $resourceType, ?bool $enabled): void
    {
        if (null === $enabled) {
            unset($this->resourceEnabled[$resourceType][$resource]);
        } else {
            $this->resourceEnabled[$resourceType][$resource] = $enabled;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isResourceEnabled(
        string $resource,
        string $resourceType,
        object|int|null $scopeIdentifier = null
    ): bool {
        return $this->resourceEnabled[$resourceType][$resource]
            ?? parent::isResourceEnabled($resource, $resourceType, $scopeIdentifier);
    }
}
