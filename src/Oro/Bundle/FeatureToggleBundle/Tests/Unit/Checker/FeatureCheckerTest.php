<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Cache;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;

class FeatureCheckerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The strategy "wrong_strategy" is not supported.
     */
    public function testInvalidArgumentException()
    {
        /** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager = $this->getMockBuilder(ConfigurationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        new FeatureChecker($configManager, [], 'wrong_strategy');
    }

    public function testSetVoters()
    {
        /** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager = $this->getMockBuilder(ConfigurationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $checker = new FeatureChecker($configManager);

        $voter = $this->getMock(VoterInterface::class);
        $checker->setVoters([$voter]);

        $this->assertAttributeCount(1, 'voters', $checker);
        $this->assertAttributeEquals([$voter], 'voters', $checker);
    }

    /**
     * @dataProvider strategyDataProvider
     *
     * @param int $strategy
     * @param array $voters
     * @param bool $allowIfAllAbstainDecisions
     * @param bool $allowIfEqualGrantedDeniedDecisions
     * @param bool $expected
     */
    public function testIsFeatureEnabledStrategies(
        $strategy,
        array $voters,
        $allowIfAllAbstainDecisions,
        $allowIfEqualGrantedDeniedDecisions,
        $expected
    ) {
        /** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager = $this->getMockBuilder(ConfigurationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->any())
            ->method('getFeatureDependencies')
            ->willReturnMap([
                ['feature1', ['feature2']],
                ['feature2', []]
            ]);

        $checker = new FeatureChecker(
            $configManager,
            $voters,
            $strategy,
            $allowIfAllAbstainDecisions,
            $allowIfEqualGrantedDeniedDecisions
        );

        $this->assertSame($expected, $checker->isFeatureEnabled('feature1', 42));
    }

    /**
     * @dataProvider strategyDataProvider
     *
     * @param int $strategy
     * @param array $voters
     * @param bool $allowIfAllAbstainDecisions
     * @param bool $allowIfEqualGrantedDeniedDecisions
     * @param bool $expected
     */
    public function testIsResourceEnabledStrategies(
        $strategy,
        array $voters,
        $allowIfAllAbstainDecisions,
        $allowIfEqualGrantedDeniedDecisions,
        $expected
    ) {
        /** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager = $this->getMockBuilder(ConfigurationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->once())
            ->method('getFeaturesByResource')
            ->willReturn(['feature1']);

        $configManager->expects($this->any())
            ->method('getFeatureDependencies')
            ->willReturnMap([
                ['feature1', ['feature2']],
                ['feature2', []]
            ]);

        $checker = new FeatureChecker(
            $configManager,
            $voters,
            $strategy,
            $allowIfAllAbstainDecisions,
            $allowIfEqualGrantedDeniedDecisions
        );

        $this->assertSame($expected, $checker->isResourceEnabled('oro_login', 'route', 42));
    }

    /**
     * @return array
     */
    public function strategyDataProvider()
    {
        return [
            // affirmative
            [FeatureChecker::STRATEGY_AFFIRMATIVE, $this->getVoters(1, 0, 0), false, true, true],
            [FeatureChecker::STRATEGY_AFFIRMATIVE, $this->getVoters(1, 2, 0), false, true, true],
            [FeatureChecker::STRATEGY_AFFIRMATIVE, $this->getVoters(0, 1, 0), false, true, false],
            [FeatureChecker::STRATEGY_AFFIRMATIVE, $this->getVoters(0, 0, 1), false, true, false],
            [FeatureChecker::STRATEGY_AFFIRMATIVE, $this->getVoters(0, 0, 1), true, true, true],

            // consensus
            [FeatureChecker::STRATEGY_CONSENSUS, $this->getVoters(1, 0, 0), false, true, true],
            [FeatureChecker::STRATEGY_CONSENSUS, $this->getVoters(1, 2, 0), false, true, false],
            [FeatureChecker::STRATEGY_CONSENSUS, $this->getVoters(2, 1, 0), false, true, true],

            [FeatureChecker::STRATEGY_CONSENSUS, $this->getVoters(0, 0, 1), false, true, false],

            [FeatureChecker::STRATEGY_CONSENSUS, $this->getVoters(0, 0, 1), true, true, true],

            [FeatureChecker::STRATEGY_CONSENSUS, $this->getVoters(2, 2, 0), false, true, true],
            [FeatureChecker::STRATEGY_CONSENSUS, $this->getVoters(2, 2, 1), false, true, true],

            [FeatureChecker::STRATEGY_CONSENSUS, $this->getVoters(2, 2, 0), false, false, false],
            [FeatureChecker::STRATEGY_CONSENSUS, $this->getVoters(2, 2, 1), false, false, false],

            // unanimous
            [FeatureChecker::STRATEGY_UNANIMOUS, $this->getVoters(1, 0, 0), false, true, true],
            [FeatureChecker::STRATEGY_UNANIMOUS, $this->getVoters(1, 0, 1), false, true, true],
            [FeatureChecker::STRATEGY_UNANIMOUS, $this->getVoters(1, 1, 0), false, true, false],

            [FeatureChecker::STRATEGY_UNANIMOUS, $this->getVoters(0, 0, 2), false, true, false],
            [FeatureChecker::STRATEGY_UNANIMOUS, $this->getVoters(0, 0, 2), true, true, true]
        ];
    }

    public function testResetCache()
    {
        /** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager = $this->getMockBuilder(ConfigurationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $checker = new FeatureChecker($configManager);
        $this->assertFalse($checker->isFeatureEnabled('feature1'));

        $this->assertAttributeNotEmpty('featuresStates', $checker);

        $checker->resetCache();

        $this->assertAttributeEmpty('featuresStates', $checker);
    }

    /**
     * @param int $enabled
     * @param int $disabled
     * @param int $abstains
     * @return array
     */
    protected function getVoters($enabled, $disabled, $abstains)
    {
        $voters = [];
        for ($i = 0; $i < $enabled; ++$i) {
            $voters[] = $this->getVoter(VoterInterface::FEATURE_ENABLED);
        }
        for ($i = 0; $i < $disabled; ++$i) {
            $voters[] = $this->getVoter(VoterInterface::FEATURE_DISABLED);
        }
        for ($i = 0; $i < $abstains; ++$i) {
            $voters[] = $this->getVoter(VoterInterface::FEATURE_ABSTAIN);
        }

        return $voters;
    }

    /**
     * @param $vote
     * @return VoterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getVoter($vote)
    {
        $voter = $this->getMock(VoterInterface::class);
        $voter->expects($this->any())
            ->method('vote')
            ->will($this->returnValue($vote));

        return $voter;
    }

    /**
     * @dataProvider featureWithDependenciesDataProvider
     * @param array $featuredEnabled
     * @param bool $expected
     */
    public function testIsFeatureEnabledWithDependencies(array $featuredEnabled, $expected)
    {
        /** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager = $this->getMockBuilder(ConfigurationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->any())
            ->method('getFeatureDependencies')
            ->willReturnMap([
                ['feature1', ['feature2']],
                ['feature2', []]
            ]);

        $checker = new FeatureChecker($configManager);
        $voter = $this->getMock(VoterInterface::class);
        $voter->expects($this->any())
            ->method('vote')
            ->willReturnMap(
                [
                    ['feature1', null, $featuredEnabled['feature1']],
                    ['feature2', null, $featuredEnabled['feature2']]
                ]
            );
        $checker->setVoters([$voter]);

        $this->assertEquals($expected, $checker->isFeatureEnabled('feature1'));
    }

    /**
     * @dataProvider featureWithDependenciesDataProvider
     * @param array $featuredEnabled
     * @param bool $expected
     */
    public function testIsResourceEnabledWithDependencies(array $featuredEnabled, $expected)
    {
        $scopeIdentifier = new \stdClass();

        /** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager = $this->getMockBuilder(ConfigurationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->any())
            ->method('getFeatureDependencies')
            ->willReturnMap([
                ['feature1', ['feature2']],
                ['feature2', []]
            ]);
        $configManager->expects($this->once())
            ->method('getFeaturesByResource')
            ->with('type', 'test')
            ->willReturn(['feature1']);

        $checker = new FeatureChecker($configManager);
        $voter = $this->getMock(VoterInterface::class);
        $voter->expects($this->any())
            ->method('vote')
            ->willReturnMap(
                [
                    ['feature1', $scopeIdentifier, $featuredEnabled['feature1']],
                    ['feature2', $scopeIdentifier, $featuredEnabled['feature2']]
                ]
            );
        $checker->setVoters([$voter]);

        $this->assertEquals($expected, $checker->isResourceEnabled('test', 'type', $scopeIdentifier));
    }

    /**
     * @return array
     */
    public function featureWithDependenciesDataProvider()
    {
        return [
            'both enabled' => [['feature1' => true, 'feature2' => true], true],
            'feature disabled' => [['feature1' => false, 'feature2' => true], false],
            'dependency disabled' => [['feature1' => true, 'feature2' => false], false],
        ];
    }
}
