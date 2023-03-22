<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateVisibilitiesForOrganizationTopic;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateVisibilitiesTopic;
use Oro\Bundle\EmailBundle\Async\UpdateVisibilitiesProcessor;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class UpdateVisibilitiesProcessorTest extends OrmTestCase
{
    use MessageQueueExtension;

    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var EntityManagerInterface */
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
            [UpdateVisibilitiesTopic::getName()],
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

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT o0_.id AS id_0 FROM oro_organization o0_ ORDER BY o0_.id ASC',
            [['id_0' => $organizationId]]
        );
        $rootJob = new Job();
        $rootJob->setName($jobName);
        $this->jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->with($message)
            ->willReturnCallback(function ($message, $runCallback) use ($rootJob) {
                return $runCallback($this->jobRunner, $rootJob);
            });
        $job = new Job();
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
            UpdateVisibilitiesForOrganizationTopic::getName(),
            ['jobId' => $jobId, 'organizationId' => $organizationId]
        );
    }
}
