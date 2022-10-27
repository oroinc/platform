<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Storage;

/**
 * Failed Features storage.
 */
class FailedFeatures
{
    private array $featureIds = [];

    public function addFailureFeature(string $featureId): void
    {
        if (!$this->isFailureFeature($featureId)) {
            $this->featureIds[] = $featureId;
        }
    }

    public function isFailureFeature(string $featureId): bool
    {
        return in_array($featureId, $this->featureIds);
    }

    public function clear(): void
    {
        $this->featureIds = [];
    }
}
