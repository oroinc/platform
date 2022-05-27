<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Checker;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureDecisionManagerInterface;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;

class FeatureCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FeatureDecisionManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $featureDecisionManager;

    /** @var FeatureChecker */
    private $featureChecker;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigurationManager::class);
        $this->featureDecisionManager = $this->createMock(FeatureDecisionManagerInterface::class);

        $this->featureChecker = new FeatureChecker(
            $this->configManager,
            $this->featureDecisionManager
        );
    }

    public function testIsFeatureEnabled(): void
    {
        $feature = 'feature1';
        $scopeIdentifier = 1;
        $expected = true;

        $this->featureDecisionManager->expects(self::once())
            ->method('decide')
            ->with($feature, $scopeIdentifier)
            ->willReturn($expected);

        $this->assertSame($expected, $this->featureChecker->isFeatureEnabled($feature, $scopeIdentifier));
    }

    public function testIsResourceEnabled(): void
    {
        $resource = 'oro_login';
        $resourceType = 'route';
        $scopeIdentifier = 1;
        $feature = 'feature1';
        $expected = true;

        $this->configManager->expects(self::once())
            ->method('getFeaturesByResource')
            ->with($resourceType, $resource)
            ->willReturn([$feature]);
        $this->featureDecisionManager->expects(self::once())
            ->method('decide')
            ->with($feature, $scopeIdentifier)
            ->willReturn($expected);

        $this->assertSame(
            $expected,
            $this->featureChecker->isResourceEnabled($resource, $resourceType, $scopeIdentifier)
        );
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
        foreach ($featuresState as $feature => $state) {
            $decideMap[] = [$feature, null, $state];
        }

        $this->configManager->expects(self::any())
            ->method('getResourcesByType')
            ->with($resourceType)
            ->willReturn($resources);
        $this->featureDecisionManager->expects(self::any())
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
