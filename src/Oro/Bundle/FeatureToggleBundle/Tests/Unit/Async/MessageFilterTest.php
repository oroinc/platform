<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Async;

use Oro\Bundle\FeatureToggleBundle\Async\MessageFilter;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;

class MessageFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var MessageFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->filter = new MessageFilter($this->featureChecker);
    }

    public function testApply()
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('topic1', 'topic1.message1');
        $buffer->addMessage('topic1', 'topic1.message2');
        $buffer->addMessage('topic2', 'topic2.message1');

        $this->featureChecker->expects($this->exactly(2))
            ->method('isResourceEnabled')
            ->withConsecutive(
                ['topic1', 'mq_topics'],
                ['topic2', 'mq_topics']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );

        $this->filter->apply($buffer);

        $this->assertFalse($buffer->hasMessagesForTopic('topic1'));
        $this->assertTrue($buffer->hasMessagesForTopic('topic2'));
    }
}
