<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Dbal;

use Oro\Component\MessageQueue\Consumption\Dbal\DbalCliProcessManager;

class DbalCliProcessManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldReturnListOfProcessesPids()
    {
        $processManager = new DbalCliProcessManager();

        $pids = $processManager->getListOfProcessesPids('');

        $this->assertGreaterThan(0, count($pids));
        $this->assertInternalType('integer', $pids[0]);
    }
}
