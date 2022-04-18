<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Async\UpdateVisibilitiesProcessor;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;

class UpdateVisibilitiesProcessorTest extends OrmTestCase
{
    use MessageQueueExtension;

    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var EntityManagerMock */
    private $em;

    /** @var UpdateVisibilitiesProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(
            new AnnotationReader(),
            [dirname((new \ReflectionClass(Organization::class))->getFileName())]
        ));

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Organization::class)
            ->willReturn($this->em);

        $this->processor = new UpdateVisibilitiesProcessor(
            $doctrine,
            self::getMessageProducer(),
            $this->jobRunner
        );
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [Topics::UPDATE_VISIBILITIES],
            UpdateVisibilitiesProcessor::getSubscribedTopics()
        );
    }

    public function testProcess(): void
    {
        $organizationId = 1;
        $jobName = 'oro:email:update-visibilities:email-addresses';

        $messageId = 'test_message';
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getMessageId')
            ->willReturn($messageId);

        $jobId = 123;
        $job = new Job();

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT o0_.id AS id_0 FROM oro_organization o0_ ORDER BY o0_.id ASC',
            [['id_0' => $organizationId]]
        );

        $this->jobRunner->expects(self::once())
            ->method('runUnique')
            ->with($messageId, $jobName)
            ->willReturnCallback(function ($ownerId, $name, $runCallback) {
                return $runCallback($this->jobRunner, new Job());
            });
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->with(sprintf('%s:%d', $jobName, $organizationId))
            ->willReturnCallback(function ($name, $startCallback) use ($job, $jobId) {
                $job->setId($jobId);

                return $startCallback($this->jobRunner, $job);
            });

        $result = $this->processor->process(
            $message,
            $this->createMock(SessionInterface::class)
        );
        self::assertEquals(MessageProcessorInterface::ACK, $result);

        self::assertMessageSent(
            Topics::UPDATE_VISIBILITIES_FOR_ORGANIZATION,
            ['jobId' => $jobId, 'organizationId' => $organizationId]
        );
    }
}
