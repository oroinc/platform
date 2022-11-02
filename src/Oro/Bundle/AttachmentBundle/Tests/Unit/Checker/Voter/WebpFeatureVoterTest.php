<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Checker\Voter;

use Oro\Bundle\AttachmentBundle\Checker\Voter\WebpFeatureVoter;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

class WebpFeatureVoterTest extends \PHPUnit\Framework\TestCase
{
    public function testVoteReturnsAbstainWhenFeatureNotSupported(): void
    {
        self::assertEquals(VoterInterface::FEATURE_ABSTAIN, (new WebpFeatureVoter(''))->vote('some_feature'));
    }

    /**
     * @dataProvider getVoteDataProvider
     */
    public function testVote(string $webpStrategy, int $expectedResult): void
    {
        self::assertEquals($expectedResult, (new WebpFeatureVoter($webpStrategy))->vote('attachment_webp'));
    }

    public function getVoteDataProvider(): array
    {
        return [
            'returns enabled when webp strategy is enabled for all' => [
                'webpStrategy' => WebpConfiguration::ENABLED_FOR_ALL,
                'expectedResult' => VoterInterface::FEATURE_ENABLED,
            ],
            'returns enabled when webp strategy is enabled if supported' => [
                'webpStrategy' => WebpConfiguration::ENABLED_IF_SUPPORTED,
                'expectedResult' => VoterInterface::FEATURE_ENABLED,
            ],
            'returns disabled when webp strategy is disabled' => [
                'webpStrategy' => WebpConfiguration::DISABLED,
                'expectedResult' => VoterInterface::FEATURE_DISABLED,
            ],
        ];
    }
}
