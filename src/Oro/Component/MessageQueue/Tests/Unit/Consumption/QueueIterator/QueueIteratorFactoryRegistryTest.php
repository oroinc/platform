<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\QueueIterator;

use Oro\Component\MessageQueue\Consumption\QueueIterator\QueueIteratorFactoryInterface;
use Oro\Component\MessageQueue\Consumption\QueueIterator\QueueIteratorFactoryRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class QueueIteratorFactoryRegistryTest extends TestCase
{
    /**
     * @param array<string, QueueIteratorFactoryInterface> $factories
     *
     * @return QueueIteratorFactoryRegistry
     */
    private function createRegistry(array $factories): QueueIteratorFactoryRegistry
    {
        $locator = new ServiceLocator(
            array_map(static fn ($factory) => static fn () => $factory, $factories)
        );

        return new QueueIteratorFactoryRegistry($locator);
    }

    public function testGetSupportedConsumptionModesReturnsAllRegisteredModes(): void
    {
        $queueIteratorFactoryRegistry = $this->createRegistry([
            'mode-a' => $this->createMock(QueueIteratorFactoryInterface::class),
            'mode-b' => $this->createMock(QueueIteratorFactoryInterface::class),
        ]);

        $consumptionModes = $queueIteratorFactoryRegistry->getConsumptionModes();

        self::assertContains('mode-a', $consumptionModes);
        self::assertContains('mode-b', $consumptionModes);
        self::assertCount(2, $consumptionModes);
    }

    public function testGetQueueIteratorFactoryReturnsCorrectFactoryForMode(): void
    {
        $queueIteratorFactory = $this->createMock(QueueIteratorFactoryInterface::class);

        $queueIteratorFactoryRegistry = $this->createRegistry(['my-mode' => $queueIteratorFactory]);

        $result = $queueIteratorFactoryRegistry->getQueueIteratorFactory('my-mode');

        self::assertSame($queueIteratorFactory, $result);
    }

    public function testGetQueueIteratorFactoryThrowsLogicExceptionForUnknownMode(): void
    {
        $queueIteratorFactoryRegistry = $this->createRegistry([
            'known-mode' => $this->createMock(QueueIteratorFactoryInterface::class),
        ]);

        $this->expectException(\LogicException::class);

        $queueIteratorFactoryRegistry->getQueueIteratorFactory('unknown-mode');
    }

    public function testEmptyFactoriesProducesEmptySupportedModes(): void
    {
        $queueIteratorFactoryRegistry = $this->createRegistry([]);

        self::assertSame([], $queueIteratorFactoryRegistry->getConsumptionModes());
    }
}
