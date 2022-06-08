<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Command;

use Oro\Bundle\CronBundle\Command\CronCommandFeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class CronCommandFeatureCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var CronCommandFeatureChecker */
    private $checker;

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
