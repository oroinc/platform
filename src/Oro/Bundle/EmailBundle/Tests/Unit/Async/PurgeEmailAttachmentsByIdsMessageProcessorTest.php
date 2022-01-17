<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\PurgeEmailAttachmentsByIdsMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topic\PurgeEmailAttachmentsByIdsTopic;
use Oro\Component\MessageQueue\Job\JobRunner;

class PurgeEmailAttachmentsByIdsMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        $this->expectNotToPerformAssertions();

        new PurgeEmailAttachmentsByIdsMessageProcessor(
            $this->createMock(ManagerRegistry::class),
            $this->createMock(JobRunner::class)
        );
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [PurgeEmailAttachmentsByIdsTopic::getName()],
            PurgeEmailAttachmentsByIdsMessageProcessor::getSubscribedTopics()
        );
    }
}
