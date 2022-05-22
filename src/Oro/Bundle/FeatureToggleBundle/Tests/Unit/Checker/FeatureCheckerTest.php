<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Checker;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures\Voter;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FeatureCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigurationManager */
    private $configurationManager;

    protected function setUp(): void
    {
        $this->configurationManager = $this->createMock(ConfigurationManager::class);
    }

    public function testInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The strategy "wrong_strategy" is not supported.');

        new FeatureChecker($this->configurationManager, [], 'wrong_strategy', false, true);
    }

    /**
     * @dataProvider strategyDataProvider
     */
    public function testIsFeatureEnabledStrategies(
        string $strategy,
        array $voters,
        string $configNode,
        object|int $scopeIdentifier,
        bool $allowIfAllAbstainDecisions,
        bool $allowIfEqualGrantedDeniedDecisions,
        bool $expected
    ) {
        $this->configurationManager->expects($this->any())
            ->method('get')
            ->withConsecutive(
                ['feature1', 'strategy', $strategy],
                ['feature1', $configNode, $allowIfAllAbstainDecisions]
            )
            ->willReturnArgument(2);

        $checker = new FeatureChecker(
            $this->configurationManager,
            $voters,
            $strategy,
            $allowIfAllAbstainDecisions,
            $allowIfEqualGrantedDeniedDecisions
        );

        $this->assertSame($expected, $checker->isFeatureEnabled('feature1', $scopeIdentifier));
    }

    /**
     * @dataProvider strategyDataProvider
     */
    public function testIsResourceEnabledStrategies(
        string $strategy,
        array $voters,
        string $configNode,
        object|int $scopeIdentifier,
        bool $allowIfAllAbstainDecisions,
        bool $allowIfEqualGrantedDeniedDecisions,
        bool $expected
    ) {
        $this->configurationManager->expects($this->once())
            ->method('getFeaturesByResource')
            ->with('route', 'oro_login')
            ->willReturn(['feature1']);

        $this->configurationManager->expects($this->any())
            ->method('get')
            ->withConsecutive(
                ['feature1', 'strategy', $strategy],
                ['feature1', $configNode, $allowIfAllAbstainDecisions]
            )
            ->willReturnArgument(2);

        $checker = new FeatureChecker(
            $this->configurationManager,
            $voters,
            $strategy,
            $allowIfAllAbstainDecisions,
            $allowIfEqualGrantedDeniedDecisions
        );

        $this->assertSame($expected, $checker->isResourceEnabled('oro_login', 'route', $scopeIdentifier));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function strategyDataProvider(): array
    {
        return [
            // cover cache key generator case with objects
            [
                FeatureChecker::STRATEGY_AFFIRMATIVE,
                $this->getVoters(1, 0, 0),
                'allow_if_all_abstain',
                new \stdClass(),
                false,
                true,
                true
            ],

            // affirmative
            [
                FeatureChecker::STRATEGY_AFFIRMATIVE,
                $this->getVoters(1, 0, 0),
                'allow_if_all_abstain',
                42,
                false,
                true,
                true
            ],
            [
                FeatureChecker::STRATEGY_AFFIRMATIVE,
                $this->getVoters(1, 2, 0),
                'allow_if_all_abstain',
                42,
                false,
                true,
                true
            ],
            [
                FeatureChecker::STRATEGY_AFFIRMATIVE,
                $this->getVoters(0, 0, 1),
                'allow_if_all_abstain',
                42,
                true,
                true,
                true
            ],
            [
                FeatureChecker::STRATEGY_AFFIRMATIVE,
                $this->getVoters(0, 1, 0),
                'allow_if_all_abstain',
                42,
                false,
                true,
                false
            ],
            [
                FeatureChecker::STRATEGY_AFFIRMATIVE,
                $this->getVoters(0, 0, 1),
                'allow_if_all_abstain',
                42,
                false,
                true,
                false
            ],

            // consensus
            [
                FeatureChecker::STRATEGY_CONSENSUS,
                $this->getVoters(1, 0, 0),
                'allow_if_all_abstain',
                42,
                false,
                true,
                true
            ],
            [
                FeatureChecker::STRATEGY_CONSENSUS,
                $this->getVoters(1, 2, 0),
                'allow_if_all_abstain',
                42,
                false,
                true,
                false
            ],
            [
                FeatureChecker::STRATEGY_CONSENSUS,
                $this->getVoters(2, 1, 0),
                'allow_if_all_abstain',
                42,
                false,
                true,
                true
            ],

            [
                FeatureChecker::STRATEGY_CONSENSUS,
                $this->getVoters(0, 0, 1),
                'allow_if_all_abstain',
                42,
                false,
                true,
                false
            ],

            [
                FeatureChecker::STRATEGY_CONSENSUS,
                $this->getVoters(0, 0, 1),
                'allow_if_all_abstain',
                42,
                true,
                true,
                true
            ],

            [
                FeatureChecker::STRATEGY_CONSENSUS,
                $this->getVoters(2, 2, 0),
                'allow_if_equal_granted_denied',
                42,
                true,
                true,
                true
            ],
            [
                FeatureChecker::STRATEGY_CONSENSUS,
                $this->getVoters(2, 2, 1),
                'allow_if_equal_granted_denied',
                42,
                true,
                true,
                true
            ],

            [
                FeatureChecker::STRATEGY_CONSENSUS,
                $this->getVoters(2, 2, 0),
                'allow_if_equal_granted_denied',
                42,
                false,
                false,
                false
            ],
            [
                FeatureChecker::STRATEGY_CONSENSUS,
                $this->getVoters(2, 2, 1),
                'allow_if_equal_granted_denied',
                42,
                false,
                false,
                false
            ],

            // unanimous
            [
                FeatureChecker::STRATEGY_UNANIMOUS,
                $this->getVoters(1, 0, 0),
                'allow_if_all_abstain',
                42,
                false,
                true,
                true
            ],
            [
                FeatureChecker::STRATEGY_UNANIMOUS,
                $this->getVoters(1, 0, 1),
                'allow_if_all_abstain',
                42,
                false,
                true,
                true
            ],
            [
                FeatureChecker::STRATEGY_UNANIMOUS,
                $this->getVoters(1, 1, 0),
                'allow_if_all_abstain',
                42,
                false,
                true,
                false
            ],

            [
                FeatureChecker::STRATEGY_UNANIMOUS,
                $this->getVoters(0, 0, 2),
                'allow_if_all_abstain',
                42,
                false,
                true,
                false
            ],
            [
                FeatureChecker::STRATEGY_UNANIMOUS,
                $this->getVoters(0, 0, 2),
                'allow_if_all_abstain',
                42,
                true,
                true,
                true
            ],
        ];
    }

    public function testResetCache()
    {
        $this->configurationManager->expects(self::any())
            ->method('get')
            ->withConsecutive(
                ['feature1', 'strategy', FeatureChecker::STRATEGY_UNANIMOUS],
                ['feature1', 'allow_if_all_abstain', false]
            )
            ->willReturnArgument(2);

        $checker = new FeatureChecker(
            $this->configurationManager,
            [],
            FeatureChecker::STRATEGY_UNANIMOUS,
            false,
            true
        );
        self::assertFalse($checker->isFeatureEnabled('feature1'));
        self::assertNotEmpty(ReflectionUtil::getPropertyValue($checker, 'featuresStates'));

        $checker->resetCache();
        self::assertEmpty(ReflectionUtil::getPropertyValue($checker, 'featuresStates'));
    }

    /**
     * @dataProvider getDisabledResourcesByTypeProvider
     */
    public function testGetDisabledResourcesByType($resourceType, array $resources, Voter $voter, $expectedResources)
    {
        $this->configurationManager->expects($this->any())
            ->method('getResourcesByType')
            ->with($resourceType)
            ->willReturn($resources);

        $this->configurationManager->expects($this->any())
            ->method('get')
            ->willReturnArgument(2);

        $featureChecker = new FeatureChecker(
            $this->configurationManager,
            [$voter],
            FeatureChecker::STRATEGY_UNANIMOUS,
            false,
            true
        );
        $this->assertEquals($expectedResources, $featureChecker->getDisabledResourcesByType($resourceType));
    }

    public function getDisabledResourcesByTypeProvider(): array
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
                    'feature1' => VoterInterface::FEATURE_ENABLED,
                    'feature2' => VoterInterface::FEATURE_ENABLED,
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
                    'feature1' => VoterInterface::FEATURE_ENABLED,
                    'feature2' => VoterInterface::FEATURE_DISABLED,
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
                    'feature1' => VoterInterface::FEATURE_DISABLED,
                    'feature2' => VoterInterface::FEATURE_DISABLED,
                ]),
                [
                    'resource',
                    'resource2',
                ],
            ],
        ];
    }

    public function testUnsupportedStrategyCaseForCheck()
    {
        $this->configurationManager->expects($this->any())
            ->method('get')
            ->with('feature1', 'strategy', FeatureChecker::STRATEGY_UNANIMOUS)
            ->willReturn('unsupported');

        $checker = new FeatureChecker(
            $this->configurationManager,
            [],
            FeatureChecker::STRATEGY_UNANIMOUS,
            false,
            true
        );

        $this->assertTrue($checker->isFeatureEnabled('feature1'));
    }

    private function getVoters(int $enabled, int $disabled, int $abstains): array
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

    private function getVoter(int $vote): VoterInterface
    {
        $voter = $this->createMock(VoterInterface::class);
        $voter->expects($this->any())
            ->method('vote')
            ->willReturn($vote);

        return $voter;
    }
}
