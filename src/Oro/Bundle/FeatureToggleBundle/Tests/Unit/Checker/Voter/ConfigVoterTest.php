<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Checker\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\ConfigVoter;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;

class ConfigVoterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $featureConfigManager;

    /**
     * @var ConfigVoter
     */
    protected $configVoter;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->featureConfigManager = $this->getMockBuilder(ConfigurationManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configVoter = new ConfigVoter($this->configManager, $this->featureConfigManager);
    }

    /**
     * @dataProvider voteDataProvider
     * @param bool $enabled
     * @param int $expectedVote
     */
    public function testVote($enabled, $expectedVote)
    {
        $feature = 'test';
        $scopeIdentifier = new \stdClass();
        $toggle = 'toggle.key';

        $this->featureConfigManager->expects($this->once())
            ->method('get')
            ->with($feature, ConfigVoter::TOGGLE_KEY)
            ->willReturn($toggle);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with($toggle, false, false, $scopeIdentifier)
            ->willReturn($enabled);

        $this->assertEquals($expectedVote, $this->configVoter->vote($feature, $scopeIdentifier));
    }

    /**
     * @return array
     */
    public function voteDataProvider()
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
            ->with($feature, ConfigVoter::TOGGLE_KEY)
            ->willReturn($toggle);

        $this->configManager->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(VoterInterface::FEATURE_ABSTAIN, $this->configVoter->vote($feature, $scopeIdentifier));
    }
}
