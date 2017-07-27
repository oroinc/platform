<?php

namespace Oro\Bundle\Workflow\Migration;

use Doctrine\DBAL\Connection;

use Psr\Log\LoggerInterface;

use Oro\Bundle\WorkflowBundle\Migration\RemoveProcessesQuery;

class RemoveProcessQueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connector;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->connector = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
    }

    /**
     * @param string|string[] $names
     * @param mixed $expectedParams
     * @dataProvider testCases
     */
    public function testUp($names, $expectedParams)
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

    /**
     * @return array
     */
    public function testCases()
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
