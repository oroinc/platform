<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Checker;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureDecisionManagerInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureResourceDecisionManagerInterface;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;

class FeatureCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureDecisionManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $featureDecisionManager;

    /** @var FeatureResourceDecisionManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $featureResourceDecisionManager;

    /** @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FeatureChecker */
    private $featureChecker;

    protected function setUp(): void
    {
        $this->featureDecisionManager = $this->createMock(FeatureDecisionManagerInterface::class);
        $this->featureResourceDecisionManager = $this->createMock(FeatureResourceDecisionManagerInterface::class);
        $this->configManager = $this->createMock(ConfigurationManager::class);

        $this->featureChecker = new FeatureChecker(
            $this->featureDecisionManager,
            $this->featureResourceDecisionManager,
            $this->configManager
        );
    }

    /**
     * @dataProvider checkDataProvider
     */
    public function testIsFeatureEnabled(bool $expected): void
    {
        $feature = 'feature1';
        $scopeIdentifier = 1;

        $this->featureDecisionManager->expects(self::once())
            ->method('decide')
            ->with($feature, $scopeIdentifier)
            ->willReturn($expected);

        $this->assertSame($expected, $this->featureChecker->isFeatureEnabled($feature, $scopeIdentifier));
    }

    /**
     * @dataProvider checkDataProvider
     */
    public function testIsResourceEnabled(bool $expected): void
    {
        $resource = 'oro_login';
        $resourceType = 'route';
        $scopeIdentifier = 1;

        $this->featureResourceDecisionManager->expects(self::once())
            ->method('decide')
            ->with($resource, $resourceType, $scopeIdentifier)
            ->willReturn($expected);

        $this->assertSame(
            $expected,
            $this->featureChecker->isResourceEnabled($resource, $resourceType, $scopeIdentifier)
        );
    }

    public function checkDataProvider(): array
    {
        return [
            [false],
            [true],
        ];
    }

    /**
     * @dataProvider getDisabledResourcesByTypeProvider
     */
    public function testGetDisabledResourcesByType(
        string $resourceType,
        array $resources,
        array $featuresState,
        array $expectedResources
    ): void {
        $decideMap = [];
        foreach ($resources as $resource => $features) {
            foreach ($features as $feature) {
                $decideMap[] = [$resource, $resourceType, null, $featuresState[$feature]];
            }
        }

        $this->configManager->expects(self::any())
            ->method('getResourcesByType')
            ->with($resourceType)
            ->willReturn($resources);
        $this->featureResourceDecisionManager->expects(self::any())
            ->method('decide')
            ->willReturnMap($decideMap);

        $this->assertEquals($expectedResources, $this->featureChecker->getDisabledResourcesByType($resourceType));
    }

    public function getDisabledResourcesByTypeProvider(): array
    {
        return [
            [
                'type',
                ['resource' => ['feature1'], 'resource2' => ['feature2']],
                ['feature1' => true, 'feature2' => true],
                []
            ],
            [
                'type',
                ['resource' => ['feature1'], 'resource2' => ['feature2']],
                ['feature1' => true, 'feature2' => false],
                ['resource2']
            ],
            [
                'type',
                ['resource' => ['feature1'], 'resource2' => ['feature2']],
                ['feature1' => false, 'feature2' => false],
                ['resource', 'resource2']
            ]
        ];
    }

    public function testResetCache(): void
    {
        $this->featureDecisionManager->expects(self::once())
            ->method('reset');

        $this->featureChecker->resetCache();
    }
}
