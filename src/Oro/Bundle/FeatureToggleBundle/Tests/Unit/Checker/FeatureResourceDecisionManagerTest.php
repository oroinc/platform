<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Checker;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureDecisionManagerInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureResourceDecisionManager;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FeatureResourceDecisionManagerTest extends TestCase
{
    private FeatureDecisionManagerInterface&MockObject $featureDecisionManager;
    private ConfigurationManager&MockObject $configManager;
    private FeatureResourceDecisionManager $featureResourceDecisionManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->featureDecisionManager = $this->createMock(FeatureDecisionManagerInterface::class);
        $this->configManager = $this->createMock(ConfigurationManager::class);

        $this->featureResourceDecisionManager = new FeatureResourceDecisionManager(
            $this->featureDecisionManager,
            $this->configManager
        );
    }

    /**
     * @dataProvider decideDataProvider
     */
    public function testDecide(array $features, bool $expected): void
    {
        $resource = 'oro_login';
        $resourceType = 'route';
        $scopeIdentifier = 1;

        $decideResultMap = [];
        foreach ($features as $featureName => $featureEnabled) {
            $decideResultMap[] = [$featureName, $scopeIdentifier, $featureEnabled];
        }

        $this->configManager->expects(self::once())
            ->method('getFeaturesByResource')
            ->with($resourceType, $resource)
            ->willReturn(array_keys($features));
        $this->featureDecisionManager->expects(self::atLeastOnce())
            ->method('decide')
            ->willReturnMap($decideResultMap);

        $this->assertSame(
            $expected,
            $this->featureResourceDecisionManager->decide($resource, $resourceType, $scopeIdentifier)
        );
    }

    public function decideDataProvider(): array
    {
        return [
            [['feature1' => false, 'feature2' => false], false],
            [['feature1' => false, 'feature2' => true], false],
            [['feature1' => true, 'feature2' => false], false],
            [['feature1' => true, 'feature2' => true], true],
        ];
    }
}
