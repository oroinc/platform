<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Client;

use Oro\Bundle\MessageQueueBundle\Client\ChainMessageFilter;
use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\MessageQueueBundle\Client\MessageFilterInterface;
use Psr\Container\ContainerInterface;

class ChainMessageFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testApply()
    {
        $container = $this->createMock(ContainerInterface::class);
        $chainFilter = new ChainMessageFilter(
            [
                ['filter1', 'topic1'],
                ['filter2', null],
                ['filter3', 'topic1'],
                ['filter3', 'topic2']
            ],
            $container
        );

        $buffer = new MessageBuffer();
        $buffer->addMessage('topic1', ['msg1']);

        $filter1 = $this->createMock(MessageFilterInterface::class);
        $filter2 = $this->createMock(MessageFilterInterface::class);
        $filter3 = $this->createMock(MessageFilterInterface::class);

        $container->expects(self::exactly(3))
            ->method('get')
            ->withConsecutive(['filter1'], ['filter2'], ['filter3'])
            ->willReturnOnConsecutiveCalls($filter1, $filter2, $filter3);
        $filter1->expects(self::once())
            ->method('apply')
            ->with(self::identicalTo($buffer));
        $filter2->expects(self::once())
            ->method('apply')
            ->with(self::identicalTo($buffer));
        $filter3->expects(self::once())
            ->method('apply')
            ->with(self::identicalTo($buffer));

        $chainFilter->apply($buffer);
    }
}
