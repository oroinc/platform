<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Checker;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;

class FeatureCheckerHolderTraitTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider isFeatureEnabledDataProvider
     */
    public function testIsFeatureEnabled($expectedStatus)
    {
        $checkerMock = $this->createMock(FeatureChecker::class);
        $checkerMock->method('isFeatureEnabled')->with('feature_name')->willReturn($expectedStatus);
        $service = $this->getObjectForTrait(FeatureCheckerHolderTrait::class);
        $service->setFeatureChecker($checkerMock);
        $service->addFeature('feature_name');
        $this->assertEquals($expectedStatus, $service->isFeaturesEnabled());
    }

    public function isFeatureEnabledDataProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
