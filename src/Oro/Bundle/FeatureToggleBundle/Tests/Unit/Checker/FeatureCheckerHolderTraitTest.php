<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Checker;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use PHPUnit\Framework\TestCase;

class FeatureCheckerHolderTraitTest extends TestCase
{
    /**
     * @dataProvider isFeatureEnabledDataProvider
     */
    public function testIsFeatureEnabled($expectedStatus): void
    {
        $checkerMock = $this->createMock(FeatureChecker::class);
        $checkerMock->expects(self::any())
            ->method('isFeatureEnabled')
            ->with('feature_name')
            ->willReturn($expectedStatus);
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
