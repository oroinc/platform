<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Checker\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\ConfigVoter;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigVoterTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private ConfigurationManager&MockObject $featureConfigManager;
    private ConfigVoter $configVoter;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->featureConfigManager = $this->createMock(ConfigurationManager::class);

        $this->configVoter = new ConfigVoter($this->configManager, $this->featureConfigManager);
    }

    /**
     * @dataProvider voteDataProvider
     */
    public function testVote(bool $enabled, int $expectedVote): void
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

    public function testVoteAbstain(): void
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
