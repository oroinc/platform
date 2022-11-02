<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Checker\Voter;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\DependencyVoter;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;

class DependencyVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $featureConfigManager;

    /** @var DependencyVoter */
    private $dependencyVoter;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->featureConfigManager = $this->createMock(ConfigurationManager::class);

        $this->dependencyVoter = new DependencyVoter($this->featureChecker, $this->featureConfigManager);
    }

    /**
     * @dataProvider voteDataProvider
     */
    public function testVote(bool $enabled, int $expectedVote)
    {
        $feature = 'feature1';
        $dependent = 'feature2';

        $this->featureConfigManager->expects($this->once())
            ->method('getFeatureDependencies')
            ->with($feature)
            ->willReturn([$dependent]);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with($dependent)
            ->willReturn($enabled);

        $this->assertEquals($expectedVote, $this->dependencyVoter->vote($feature));
    }

    public function voteDataProvider(): array
    {
        return [
            [true, DependencyVoter::FEATURE_ENABLED],
            [false, DependencyVoter::FEATURE_DISABLED]
        ];
    }

    public function testVoteAbstain()
    {
        $feature = 'feature1';

        $this->featureConfigManager->expects($this->once())
            ->method('getFeatureDependencies')
            ->with($feature)
            ->willReturn([]);

        $this->featureChecker->expects($this->never())
            ->method('isFeatureEnabled');

        $this->assertEquals(DependencyVoter::FEATURE_ABSTAIN, $this->dependencyVoter->vote($feature));
    }
}
