<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Functional\Stub;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * The decorator for FeatureChecker that allows to configure the feature checker in functional tests.
 */
class FeatureCheckerStub extends FeatureChecker
{
    private array $featureEnabled = [];
    private array $resourceEnabled = [];
    private array $resourceTypeEnabled = [];

    public function setFeatureEnabled(string $feature, ?bool $enabled): void
    {
        if (null === $enabled) {
            unset($this->featureEnabled[$feature]);
        } else {
            $this->featureEnabled[$feature] = $enabled;
        }
    }

    public function setResourceEnabled(string $resource, string $resourceType, ?bool $enabled): void
    {
        if (null === $enabled) {
            unset($this->resourceEnabled[$resourceType][$resource]);
        } else {
            $this->resourceEnabled[$resourceType][$resource] = $enabled;
        }
    }

    public function setResourceTypeEnabled(string $resourceType, ?bool $enabled): void
    {
        if (null === $enabled) {
            unset($this->resourceTypeEnabled[$resourceType]);
        } else {
            $this->resourceTypeEnabled[$resourceType] = $enabled;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isFeatureEnabled(string $feature, object|int|null $scopeIdentifier = null): bool
    {
        return
            $this->featureEnabled[$feature]
            ?? parent::isFeatureEnabled($feature, $scopeIdentifier);
    }

    /**
     * {@inheritdoc}
     */
    public function isResourceEnabled(
        string $resource,
        string $resourceType,
        object|int|null $scopeIdentifier = null
    ): bool {
        return
            $this->resourceTypeEnabled[$resourceType]
            ?? $this->resourceEnabled[$resourceType][$resource]
            ?? parent::isResourceEnabled($resource, $resourceType, $scopeIdentifier);
    }
}
