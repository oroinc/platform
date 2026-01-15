<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Storage;

/**
 * Failed Features storage.
 */
class FailedFeatures
{
    /**
     * @var array<string>
     */
    private array $featureTitles = [];

    public function addFailureFeature(string $featureTitle): void
    {
        if (!$this->isFailureFeature($featureTitle)) {
            $this->featureTitles[] = $featureTitle;
        }
    }

    public function isFailureFeature(string $featureTitle): bool
    {
        return in_array($featureTitle, $this->featureTitles, true);
    }

    public function clear(): void
    {
        $this->featureTitles = [];
    }
}
