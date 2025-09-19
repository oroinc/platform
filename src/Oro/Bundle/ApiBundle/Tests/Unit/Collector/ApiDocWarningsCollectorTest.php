<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collector;

use Oro\Bundle\ApiBundle\Collector\ApiDocWarningsCollector;
use PHPUnit\Framework\TestCase;

class ApiDocWarningsCollectorTest extends TestCase
{
    private ApiDocWarningsCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new ApiDocWarningsCollector();
    }

    public function testInitialState(): void
    {
        self::assertEmpty($this->collector->getWarnings());
    }

    public function testStartCollectingClearsWarnings(): void
    {
        // Add warning without collecting - should be ignored
        $this->collector->addWarning('ignored warning');

        $this->collector->startCollecting();

        self::assertEmpty($this->collector->getWarnings());
    }

    public function testAddWarningOnlyWhenCollecting(): void
    {
        // Should not add when not collecting
        $this->collector->addWarning('ignored warning');
        self::assertEmpty($this->collector->getWarnings());

        // Should add when collecting
        $this->collector->startCollecting();
        $this->collector->addWarning('collected warning');

        self::assertEquals(['collected warning'], $this->collector->getWarnings());
    }

    public function testStopCollecting(): void
    {
        $this->collector->startCollecting();
        $this->collector->addWarning('warning before stop');

        $this->collector->stopCollecting();

        // Warnings should remain after stopping
        self::assertEquals(['warning before stop'], $this->collector->getWarnings());

        // Should not add warnings after stopping
        $this->collector->addWarning('warning after stop');
        self::assertEquals(['warning before stop'], $this->collector->getWarnings());
    }

    public function testMultipleWarnings(): void
    {
        $warnings = ['warning 1', 'warning 2', 'warning 3'];

        $this->collector->startCollecting();
        foreach ($warnings as $warning) {
            $this->collector->addWarning($warning);
        }

        self::assertEquals($warnings, $this->collector->getWarnings());
    }
}
