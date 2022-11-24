<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Oro\Bundle\WorkflowBundle\Migration\RemoveProcessesQuery;
use Psr\Log\LoggerInterface;

class RemoveProcessQueryTest extends \PHPUnit\Framework\TestCase
{
    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connector;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->connector = $this->createMock(Connection::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testUp(string|array $names, array $expectedParams)
    {
        $this->connector->expects($this->once())
            ->method('executeQuery')
            ->with(
                $this->anything(),
                $expectedParams,
                $this->anything()
            );

        $removeProcessQuery = new RemoveProcessesQuery($names);
        $removeProcessQuery->setConnection($this->connector);

        $removeProcessQuery->execute($this->logger);
    }

    public function dataProvider(): array
    {
        return [
            [
                'unit_test_single_process',
                [['unit_test_single_process']]],
            [
                ['unit_test_process_one', 'unit_test_process_two'],
                [['unit_test_process_one', 'unit_test_process_two']],
            ]
        ];
    }
}
