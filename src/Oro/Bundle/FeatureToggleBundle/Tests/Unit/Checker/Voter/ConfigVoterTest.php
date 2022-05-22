<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Checker\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\ConfigVoter;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;

class ConfigVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $featureConfigManager;

    /** @var ConfigVoter */
    private $configVoter;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->featureConfigManager = $this->createMock(ConfigurationManager::class);

        $this->configVoter = new ConfigVoter($this->configManager, $this->featureConfigManager);
    }

    /**
     * @dataProvider voteDataProvider
     */
    public function testVote(bool $enabled, int $expectedVote)
    {
        $feature = 'test';
        $scopeIdentifier = new \stdClass();
        $toggle = 'toggle.key';

        $this->featureConfigManager->expects($this->once())
            ->method('get')
            ->with($feature, 'toggle')
            ->willReturn($toggle);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with($toggle, false, false, $scopeIdentifier)
            ->willReturn($enabled);

        $this->assertEquals($expectedVote, $this->configVoter->vote($feature, $scopeIdentifier));
    }

    public function voteDataProvider(): array
    {
        return [
            [true, VoterInterface::FEATURE_ENABLED],
            [false, VoterInterface::FEATURE_DISABLED]
        ];
    }

    public function testVoteAbstain()
    {
        $feature = 'test';
        $scopeIdentifier = new \stdClass();
        $toggle = null;

        $this->featureConfigManager->expects($this->once())
            ->method('get')
            ->with($feature, 'toggle')
            ->willReturn($toggle);

        $this->configManager->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(VoterInterface::FEATURE_ABSTAIN, $this->configVoter->vote($feature, $scopeIdentifier));
    }
}
