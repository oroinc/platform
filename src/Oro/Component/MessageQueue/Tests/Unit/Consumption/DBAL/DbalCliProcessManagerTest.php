<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\DBAL;

use Oro\Component\MessageQueue\Consumption\Dbal\DbalCliProcessManager;
use PHPUnit\Framework\TestCase;

class DbalCliProcessManagerTest extends TestCase
{
    public function testShouldReturnListOfProcessesPids(): void
    {
        $processManager = new DbalCliProcessManager();

        $pids = $processManager->getListOfProcessesPids('');

        self::assertGreaterThan(0, count($pids));
        self::assertIsInt($pids[0]);
    }
}
