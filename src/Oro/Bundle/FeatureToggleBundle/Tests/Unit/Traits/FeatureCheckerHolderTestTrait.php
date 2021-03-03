<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Traits;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;

trait FeatureCheckerHolderTestTrait
{
    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    protected FeatureChecker $featureChecker;

    protected FeatureToggleableInterface $testedObject;

    /** @var string[] */
    protected array $features = [];

    /**
     * @param FeatureToggleableInterface $testedObject
     * @param string[] $features
     */
    protected function setupFeatureChecker(FeatureToggleableInterface $testedObject, array $features): void
    {
        $this->testedObject = $testedObject;
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->testedObject->setFeatureChecker($this->featureChecker);

        $this->features = $features;
        foreach ($this->features as $feature) {
            $this->testedObject->addFeature($feature);
        }
    }

    protected function assertFeaturesEnabled(): void
    {
        $this->assertFeaturesState(true);
    }

    protected function assertFeaturesDisabled(): void
    {
        $this->assertFeaturesState(false);
    }

    protected function assertFeaturesState(bool $isEnabled): void
    {
        $returnMap = [];
        foreach ($this->features as $feature) {
            $returnMap[] = [$feature, null, $isEnabled];
        }

        $this->featureChecker
            ->expects(self::any())
            ->method('isFeatureEnabled')
            ->willReturnMap($returnMap);
    }
}
