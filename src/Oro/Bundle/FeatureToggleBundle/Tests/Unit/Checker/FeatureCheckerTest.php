<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Cache;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures\Voter;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FeatureCheckerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The strategy "wrong_strategy" is not supported.
     */
    public function testInvalidArgumentException()
    {
        /** @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->getMockBuilder(ConfigurationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        new FeatureChecker($configManager, [], 'wrong_strategy');
    }

    public function testSetVoters()
    {
        /** @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->getMockBuilder(ConfigurationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $checker = new FeatureChecker($configManager);

        $voter = $this->createMock(VoterInterface::class);
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
        /** @var ConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject $configurationProvider */
        $configurationProvider = $this->createMock(ConfigurationProvider::class);
        $configurationProvider->expects($this->any())
            ->method('getFeaturesConfiguration')
            ->willReturn([]);

        /** @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->getMockBuilder(ConfigurationManager::class)
            ->setConstructorArgs([$configurationProvider])
            ->setMethods(['getFeatureDependencies'])
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
        /** @var ConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject $configurationProvider */
        $configurationProvider = $this->createMock(ConfigurationProvider::class);
        $configurationProvider->expects($this->any())
            ->method('getFeaturesConfiguration')
            ->willReturn([]);

        /** @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->getMockBuilder(ConfigurationManager::class)
            ->setConstructorArgs([$configurationProvider])
            ->setMethods(['getFeatureDependencies', 'getFeaturesByResource'])
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
        /** @var ConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject $configurationProvider */
        $configurationProvider = $this->createMock(ConfigurationProvider::class);
        $configurationProvider->expects($this->any())
            ->method('getFeaturesConfiguration')
            ->willReturn([]);

        /** @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->getMockBuilder(ConfigurationManager::class)
            ->setConstructorArgs([$configurationProvider])
            ->setMethods(null)
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
     * @return VoterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getVoter($vote)
    {
        $voter = $this->createMock(VoterInterface::class);
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
        /** @var ConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject $configurationProvider */
        $configurationProvider = $this->createMock(ConfigurationProvider::class);
        $configurationProvider->expects($this->any())
            ->method('getFeaturesConfiguration')
            ->willReturn([]);

        /** @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->getMockBuilder(ConfigurationManager::class)
            ->setConstructorArgs([$configurationProvider])
            ->setMethods(['getFeatureDependencies'])
            ->getMock();

        $configManager->expects($this->any())
            ->method('getFeatureDependencies')
            ->willReturnMap([
                ['feature1', ['feature2']],
                ['feature2', []]
            ]);

        $checker = new FeatureChecker($configManager);
        $voter = $this->createMock(VoterInterface::class);
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

        /** @var ConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject $configurationProvider */
        $configurationProvider = $this->createMock(ConfigurationProvider::class);
        $configurationProvider->expects($this->any())
            ->method('getFeaturesConfiguration')
            ->willReturn([]);

        /** @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->getMockBuilder(ConfigurationManager::class)
            ->setConstructorArgs([$configurationProvider])
            ->setMethods(['getFeatureDependencies', 'getFeaturesByResource'])
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
        $voter = $this->createMock(VoterInterface::class);
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

    /**
     * @dataProvider getDisabledResourcesByTypeProvider
     */
    public function testGetDisabledResourcesByType($resourceType, array $resources, Voter $voter, $expectedResources)
    {
        /** @var ConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject $configurationProvider */
        $configurationProvider = $this->createMock(ConfigurationProvider::class);
        $configurationProvider->expects($this->any())
            ->method('getFeaturesConfiguration')
            ->willReturn([]);

        /** @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->getMockBuilder(ConfigurationManager::class)
            ->setConstructorArgs([$configurationProvider])
            ->setMethods(['getFeatureDependencies', 'getResourcesByType'])
            ->getMock();

        $configManager->expects($this->any())
            ->method('getResourcesByType')
            ->with($resourceType)
            ->will($this->returnValue($resources));
        $configManager->expects($this->any())
            ->method('getFeatureDependencies')
            ->will($this->returnValue([]));

        $featureChecker = new FeatureChecker($configManager, [$voter]);
        $this->assertEquals($expectedResources, $featureChecker->getDisabledResourcesByType($resourceType));
    }

    public function getDisabledResourcesByTypeProvider()
    {
        return [
            [
                'type',
                [
                    'resource' => [
                        'feature1',
                    ],
                    'resource2' => [
                        'feature2',
                    ],
                ],
                new Voter([
                    'feature1' => Voter::FEATURE_ENABLED,
                    'feature2' => Voter::FEATURE_ENABLED,
                ]),
                [],
            ],
            [
                'type',
                [
                    'resource' => [
                        'feature1',
                    ],
                    'resource2' => [
                        'feature2',
                    ],
                ],
                new Voter([
                    'feature1' => Voter::FEATURE_ENABLED,
                    'feature2' => Voter::FEATURE_DISABLED,
                ]),
                [
                    'resource2',
                ],
            ],
            [
                'type',
                [
                    'resource' => [
                        'feature1',
                    ],
                    'resource2' => [
                        'feature2',
                    ],
                ],
                new Voter([
                    'feature1' => Voter::FEATURE_DISABLED,
                    'feature2' => Voter::FEATURE_DISABLED,
                ]),
                [
                    'resource',
                    'resource2',
                ],
            ],
        ];
    }

    /**
     * Check that feature strategies and related options taken into account.
     *
     * @dataProvider featureStrategyDataProvider
     *
     * @param int $strategy
     * @param int $featureStrategy
     * @param array $voters
     * @param bool $allowIfAllAbstainDecisions
     * @param bool $allowIfEqualGrantedDeniedDecisions
     * @param bool $featureAllowIfAllAbstainDecisions
     * @param bool $featureAllowIfEqualGrantedDeniedDecisions
     * @param bool $expected
     */
    public function testFeatureStrategies(
        $strategy,
        $featureStrategy,
        array $voters,
        $allowIfAllAbstainDecisions,
        $allowIfEqualGrantedDeniedDecisions,
        $featureAllowIfAllAbstainDecisions,
        $featureAllowIfEqualGrantedDeniedDecisions,
        $expected
    ) {
        /** @var ConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject $configurationProvider */
        $configurationProvider = $this->createMock(ConfigurationProvider::class);
        $configurationProvider->expects($this->any())
            ->method('getFeaturesConfiguration')
            ->willReturnCallback(
                function () use (
                    $featureStrategy,
                    $featureAllowIfAllAbstainDecisions,
                    $featureAllowIfEqualGrantedDeniedDecisions
                ) {
                    $result = ['feature1' => []];
                    if ($featureStrategy) {
                        $result['feature1']['strategy'] = $featureStrategy;
                    }
                    if ($featureAllowIfAllAbstainDecisions) {
                        $result['feature1']['allow_if_all_abstain'] = $featureAllowIfAllAbstainDecisions;
                    }
                    if ($featureAllowIfEqualGrantedDeniedDecisions) {
                        $result['feature1']['allow_if_equal_granted_denied'] =
                            $featureAllowIfEqualGrantedDeniedDecisions;
                    }

                    return $result;
                }
            );
        $configurationProvider->expects($this->any())
            ->method('getDependenciesConfiguration')
            ->willReturn([]);
        $configManager = new ConfigurationManager($configurationProvider);

        $checker = new FeatureChecker(
            $configManager,
            $voters,
            $strategy,
            $allowIfAllAbstainDecisions,
            $allowIfEqualGrantedDeniedDecisions
        );

        $this->assertSame($expected, $checker->isFeatureEnabled('feature1'));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function featureStrategyDataProvider()
    {
        return [
            'feature strategy specified' => [
                'strategy' => FeatureChecker::STRATEGY_AFFIRMATIVE,
                'featureStrategy' => FeatureChecker::STRATEGY_UNANIMOUS,
                'voters' => $this->getVoters(1, 1, 0),
                'allowIfAllAbstainDecisions' => false,
                'allowIfEqualGrantedDeniedDecisions' => false,
                'featureAllowIfAllAbstainDecisions' => false,
                'featureAllowIfEqualGrantedDeniedDecisions' => false,
                'expected' => false
            ],
            'no feature strategy use default' => [
                'strategy' => FeatureChecker::STRATEGY_AFFIRMATIVE,
                'featureStrategy' => FeatureChecker::STRATEGY_UNANIMOUS,
                'voters' => $this->getVoters(1, 0, 0),
                'allowIfAllAbstainDecisions' => false,
                'allowIfEqualGrantedDeniedDecisions' => false,
                'featureAllowIfAllAbstainDecisions' => false,
                'featureAllowIfEqualGrantedDeniedDecisions' => false,
                'expected' => true
            ],
            'if allowIfAllAbstainDecisions specified for feature, use it in affirmative strategy' => [
                'strategy' => FeatureChecker::STRATEGY_AFFIRMATIVE,
                'featureStrategy' => null,
                'voters' => $this->getVoters(0, 0, 1),
                'allowIfAllAbstainDecisions' => false,
                'allowIfEqualGrantedDeniedDecisions' => false,
                'featureAllowIfAllAbstainDecisions' => true,
                'featureAllowIfEqualGrantedDeniedDecisions' => null,
                'expected' => true
            ],
            'if allowIfAllAbstainDecisions not specified for feature, use default in affirmative strategy' => [
                'strategy' => FeatureChecker::STRATEGY_AFFIRMATIVE,
                'featureStrategy' => null,
                'voters' => $this->getVoters(0, 0, 1),
                'allowIfAllAbstainDecisions' => false,
                'allowIfEqualGrantedDeniedDecisions' => false,
                'featureAllowIfAllAbstainDecisions' => null,
                'featureAllowIfEqualGrantedDeniedDecisions' => null,
                'expected' => false
            ],
            'if allowIfAllAbstainDecisions specified for feature, use it in consensus strategy' => [
                'strategy' => FeatureChecker::STRATEGY_CONSENSUS,
                'featureStrategy' => null,
                'voters' => $this->getVoters(0, 0, 1),
                'allowIfAllAbstainDecisions' => false,
                'allowIfEqualGrantedDeniedDecisions' => false,
                'featureAllowIfAllAbstainDecisions' => true,
                'featureAllowIfEqualGrantedDeniedDecisions' => null,
                'expected' => true
            ],
            'if allowIfAllAbstainDecisions not specified for feature, use default in consensus consensus' => [
                'strategy' => FeatureChecker::STRATEGY_CONSENSUS,
                'featureStrategy' => null,
                'voters' => $this->getVoters(0, 0, 1),
                'allowIfAllAbstainDecisions' => false,
                'allowIfEqualGrantedDeniedDecisions' => false,
                'featureAllowIfAllAbstainDecisions' => null,
                'featureAllowIfEqualGrantedDeniedDecisions' => null,
                'expected' => false
            ],
            'if allowIfEqualGrantedDeniedDecisions specified for feature, use it in consensus strategy' => [
                'strategy' => FeatureChecker::STRATEGY_CONSENSUS,
                'featureStrategy' => null,
                'voters' => $this->getVoters(1, 1, 0),
                'allowIfAllAbstainDecisions' => false,
                'allowIfEqualGrantedDeniedDecisions' => false,
                'featureAllowIfAllAbstainDecisions' => null,
                'featureAllowIfEqualGrantedDeniedDecisions' => true,
                'expected' => true
            ],
            'if allowIfEqualGrantedDeniedDecisions not specified for feature, use default in consensus consensus' => [
                'strategy' => FeatureChecker::STRATEGY_CONSENSUS,
                'featureStrategy' => null,
                'voters' => $this->getVoters(1, 1, 0),
                'allowIfAllAbstainDecisions' => false,
                'allowIfEqualGrantedDeniedDecisions' => false,
                'featureAllowIfAllAbstainDecisions' => null,
                'featureAllowIfEqualGrantedDeniedDecisions' => null,
                'expected' => false
            ],

            'if allowIfAllAbstainDecisions specified for feature, use it in unanimous strategy' => [
                'strategy' => FeatureChecker::STRATEGY_UNANIMOUS,
                'featureStrategy' => null,
                'voters' => $this->getVoters(0, 0, 1),
                'allowIfAllAbstainDecisions' => false,
                'allowIfEqualGrantedDeniedDecisions' => false,
                'featureAllowIfAllAbstainDecisions' => true,
                'featureAllowIfEqualGrantedDeniedDecisions' => null,
                'expected' => true
            ],
            'if allowIfAllAbstainDecisions not specified for feature, use default in unanimous consensus' => [
                'strategy' => FeatureChecker::STRATEGY_CONSENSUS,
                'featureStrategy' => null,
                'voters' => $this->getVoters(0, 0, 1),
                'allowIfAllAbstainDecisions' => false,
                'allowIfEqualGrantedDeniedDecisions' => false,
                'featureAllowIfAllAbstainDecisions' => null,
                'featureAllowIfEqualGrantedDeniedDecisions' => null,
                'expected' => false
            ],
        ];
    }
}
