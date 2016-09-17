<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Cache;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;

class FeatureCheckerHolderTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider isFeatureEnabledDataProvider
     * @param $expectedStatus
     */
    public function testIsFeatureEnabled($expectedStatus)
    {
        /** @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject $checkerMock */
        $checkerMock = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $checkerMock->method('isFeatureEnabled')->with('feature_name')->willReturn($expectedStatus);
        $service = $this->getObjectForTrait(FeatureCheckerHolderTrait::class);
        $service->setFeatureChecker($checkerMock);
        $service->addFeature('feature_name');
        $this->assertEquals($expectedStatus, $service->isFeaturesEnabled());
    }

    /**
     * @return array
     */
    public function isFeatureEnabledDataProvider()
    {
        return [
            [true],
            [false],
        ];
    }
}
