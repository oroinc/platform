<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Checker\Voter;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\ThemeBundle\Checker\Voter\ThemeConfigurationFeatureVoter;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationTypeProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ThemeConfigurationFeatureVoterTest extends TestCase
{
    private const FEATURE_NAME = 'theme_configuration';

    private ThemeConfigurationTypeProvider&MockObject $typeProvider;
    private ThemeConfigurationFeatureVoter $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->typeProvider = $this->createMock(ThemeConfigurationTypeProvider::class);
        $this->voter = new ThemeConfigurationFeatureVoter($this->typeProvider);
    }

    public function testVoteAbstainForDifferentFeature(): void
    {
        $result = $this->voter->vote('some_other_feature');

        self::assertEquals(VoterInterface::FEATURE_ABSTAIN, $result);
    }

    public function testVoteDisabledWhenTypesAreEmpty(): void
    {
        $this->typeProvider->expects(self::once())
            ->method('getTypes')
            ->willReturn([]);

        $result = $this->voter->vote(self::FEATURE_NAME);

        self::assertEquals(VoterInterface::FEATURE_DISABLED, $result);
    }

    public function testVoteEnabledWhenTypesExist(): void
    {
        $this->typeProvider->expects(self::once())
            ->method('getTypes')
            ->willReturn(['storefront', 'backoffice']);

        $result = $this->voter->vote(self::FEATURE_NAME);

        self::assertEquals(VoterInterface::FEATURE_ENABLED, $result);
    }

    public function testVoteEnabledWithSingleType(): void
    {
        $this->typeProvider->expects(self::once())
            ->method('getTypes')
            ->willReturn(['storefront']);

        $result = $this->voter->vote(self::FEATURE_NAME);

        self::assertEquals(VoterInterface::FEATURE_ENABLED, $result);
    }
}
