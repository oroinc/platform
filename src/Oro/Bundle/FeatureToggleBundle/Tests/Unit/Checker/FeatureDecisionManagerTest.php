<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Checker;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureDecisionManager;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FeatureDecisionManagerTest extends \PHPUnit\Framework\TestCase
{
    private function getVoter(int $vote): VoterInterface
    {
        $voter = $this->createMock(VoterInterface::class);
        $voter->expects(self::any())
            ->method('vote')
            ->willReturn($vote);

        return $voter;
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

    /**
     * @dataProvider strategyDataProvider
     */
    public function testDecideStrategies(
        string $strategy,
        array $voters,
        string $configNode,
        object|int $scopeIdentifier,
        bool $allowIfAllAbstainDecisions,
        bool $allowIfEqualGrantedDeniedDecisions,
        bool $expected
    ): void {
        $configManager = $this->createMock(ConfigurationManager::class);
        $configManager->expects(self::any())
            ->method('get')
            ->withConsecutive(
                ['feature1', 'strategy', $strategy],
                ['feature1', $configNode, $allowIfAllAbstainDecisions]
            )
            ->willReturnArgument(2);

        $decisionManager = new FeatureDecisionManager(
            $configManager,
            $voters,
            $strategy,
            $allowIfAllAbstainDecisions,
            $allowIfEqualGrantedDeniedDecisions
        );

        $this->assertSame($expected, $decisionManager->decide('feature1', $scopeIdentifier));
    }

    public function strategyDataProvider(): array
    {
        return [
            // cover cache key generator case with objects
            ['affirmative', $this->getVoters(1, 0, 0), 'allow_if_all_abstain', new \stdClass(), false, true, true],
            // unanimous
            ['unanimous', $this->getVoters(1, 0, 0), 'allow_if_all_abstain', 42, false, true, true],
            ['unanimous', $this->getVoters(1, 0, 1), 'allow_if_all_abstain', 42, false, true, true],
            ['unanimous', $this->getVoters(1, 1, 0), 'allow_if_all_abstain', 42, false, true, false],
            ['unanimous', $this->getVoters(0, 0, 2), 'allow_if_all_abstain', 42, false, true, false],
            ['unanimous', $this->getVoters(0, 0, 2), 'allow_if_all_abstain', 42, true, true, true],
            // affirmative
            ['affirmative', $this->getVoters(1, 0, 0), 'allow_if_all_abstain', 42, false, true, true],
            ['affirmative', $this->getVoters(1, 2, 0), 'allow_if_all_abstain', 42, false, true, true],
            ['affirmative', $this->getVoters(0, 0, 1), 'allow_if_all_abstain', 42, true, true, true],
            ['affirmative', $this->getVoters(0, 1, 0), 'allow_if_all_abstain', 42, false, true, false],
            ['affirmative', $this->getVoters(0, 0, 1), 'allow_if_all_abstain', 42, false, true, false],
            // consensus
            ['consensus', $this->getVoters(1, 0, 0), 'allow_if_all_abstain', 42, false, true, true],
            ['consensus', $this->getVoters(1, 2, 0), 'allow_if_all_abstain', 42, false, true, false],
            ['consensus', $this->getVoters(2, 1, 0), 'allow_if_all_abstain', 42, false, true, true],
            ['consensus', $this->getVoters(0, 0, 1), 'allow_if_all_abstain', 42, false, true, false],
            ['consensus', $this->getVoters(0, 0, 1), 'allow_if_all_abstain', 42, true, true, true],
            ['consensus', $this->getVoters(2, 2, 0), 'allow_if_equal_granted_denied', 42, true, true, true],
            ['consensus', $this->getVoters(2, 2, 1), 'allow_if_equal_granted_denied', 42, true, true, true],
            ['consensus', $this->getVoters(2, 2, 0), 'allow_if_equal_granted_denied', 42, false, false, false],
            ['consensus', $this->getVoters(2, 2, 1), 'allow_if_equal_granted_denied', 42, false, false, false],
        ];
    }

    public function testUnsupportedStrategyCaseForCheck(): void
    {
        $configManager = $this->createMock(ConfigurationManager::class);
        $configManager->expects(self::any())
            ->method('get')
            ->with('feature1', 'strategy', 'unanimous')
            ->willReturn('unsupported');

        $decisionManager = new FeatureDecisionManager($configManager, [], 'unanimous', false, true);

        $this->assertTrue($decisionManager->decide('feature1', null));
    }

    public function testReset(): void
    {
        $configManager = $this->createMock(ConfigurationManager::class);
        $configManager->expects(self::any())
            ->method('get')
            ->withConsecutive(
                ['feature1', 'strategy', 'unanimous'],
                ['feature1', 'allow_if_all_abstain', false]
            )
            ->willReturnArgument(2);

        $decisionManager = new FeatureDecisionManager($configManager, [], 'unanimous', false, true);

        self::assertFalse($decisionManager->decide('feature1', null));
        self::assertNotEmpty(ReflectionUtil::getPropertyValue($decisionManager, 'cache'));

        $decisionManager->reset();
        self::assertEmpty(ReflectionUtil::getPropertyValue($decisionManager, 'cache'));
    }
}
