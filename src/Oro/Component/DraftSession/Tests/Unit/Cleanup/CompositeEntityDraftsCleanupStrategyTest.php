<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Cleanup;

use Oro\Component\DraftSession\Cleanup\CompositeEntityDraftsCleanupStrategy;
use Oro\Component\DraftSession\Cleanup\EntityDraftsCleanupStrategyInterface;
use PHPUnit\Framework\TestCase;

final class CompositeEntityDraftsCleanupStrategyTest extends TestCase
{
    public function testCleanupEntityDraftsAggregatesResults(): void
    {
        $strategy1 = $this->createMock(EntityDraftsCleanupStrategyInterface::class);
        $strategy1->expects(self::once())
            ->method('cleanupEntityDrafts')
            ->willReturn(3);

        $strategy2 = $this->createMock(EntityDraftsCleanupStrategyInterface::class);
        $strategy2->expects(self::once())
            ->method('cleanupEntityDrafts')
            ->willReturn(5);

        $composite = new CompositeEntityDraftsCleanupStrategy([$strategy1, $strategy2]);

        $threshold = new \DateTime('today -7 days', new \DateTimeZone('UTC'));

        self::assertEquals(8, $composite->cleanupEntityDrafts($threshold, 100));
    }

    public function testCleanupEntityDraftsPassesThresholdAndBatchSize(): void
    {
        $threshold = new \DateTime('today -14 days', new \DateTimeZone('UTC'));

        $strategy = $this->createMock(EntityDraftsCleanupStrategyInterface::class);
        $strategy->expects(self::once())
            ->method('cleanupEntityDrafts')
            ->with(
                self::identicalTo($threshold),
                50
            )
            ->willReturn(2);

        $composite = new CompositeEntityDraftsCleanupStrategy([$strategy]);

        self::assertEquals(2, $composite->cleanupEntityDrafts($threshold, 50));
    }

    public function testCleanupEntityDraftsWithEmptyStrategies(): void
    {
        $composite = new CompositeEntityDraftsCleanupStrategy([]);

        $threshold = new \DateTime('today -7 days', new \DateTimeZone('UTC'));

        self::assertEquals(0, $composite->cleanupEntityDrafts($threshold, 100));
    }

    public function testCleanupEntityDraftsCallsAllStrategiesEvenWhenSomeReturnZero(): void
    {
        $strategy1 = $this->createMock(EntityDraftsCleanupStrategyInterface::class);
        $strategy1->expects(self::once())
            ->method('cleanupEntityDrafts')
            ->willReturn(0);

        $strategy2 = $this->createMock(EntityDraftsCleanupStrategyInterface::class);
        $strategy2->expects(self::once())
            ->method('cleanupEntityDrafts')
            ->willReturn(7);

        $strategy3 = $this->createMock(EntityDraftsCleanupStrategyInterface::class);
        $strategy3->expects(self::once())
            ->method('cleanupEntityDrafts')
            ->willReturn(0);

        $composite = new CompositeEntityDraftsCleanupStrategy([$strategy1, $strategy2, $strategy3]);

        $threshold = new \DateTime('today -7 days', new \DateTimeZone('UTC'));

        self::assertEquals(7, $composite->cleanupEntityDrafts($threshold, 100));
    }
}
