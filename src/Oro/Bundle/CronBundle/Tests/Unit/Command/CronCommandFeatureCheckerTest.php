<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Command;

use Oro\Bundle\CronBundle\Command\CronCommandFeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CronCommandFeatureCheckerTest extends TestCase
{
    private FeatureChecker&MockObject $featureChecker;
    private CronCommandFeatureChecker $checker;

    #[\Override]
    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->checker = new CronCommandFeatureChecker($this->featureChecker);
    }

    /**
     * @dataProvider isFeatureEnabledDataProvider
     */
    public function testIsFeatureEnabled(bool $result): void
    {
        $commandName = 'test';

        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($commandName, 'cron_jobs')
            ->willReturn($result);

        self::assertSame($result, $this->checker->isFeatureEnabled($commandName));
    }

    public function isFeatureEnabledDataProvider(): array
    {
        return [[false], [true]];
    }
}
