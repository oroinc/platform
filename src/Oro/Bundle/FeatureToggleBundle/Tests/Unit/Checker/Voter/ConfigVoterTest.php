<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Checker\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\ConfigVoter;

class ConfigVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject
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
}
