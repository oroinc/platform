<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Checker\Voter;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\DependencyVoter;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;

class DependencyVoterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $featureChecker;

    /**
     * @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $featureConfigManager;

    /**
     * @var DependencyVoter
     */
    protected $dependencyVoter;

    protected function setUp()
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->featureConfigManager = $this->createMock(ConfigurationManager::class);

        $this->dependencyVoter = new DependencyVoter($this->featureChecker, $this->featureConfigManager);
    }

    /**
     * @dataProvider voteDataProvider
     * @param bool $enabled
     * @param int $expectedVote
     */
    public function testVote($enabled, $expectedVote)
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

    /**
     * @return array
     */
    public function voteDataProvider()
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
