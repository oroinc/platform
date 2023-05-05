<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailVisibilitiesForOrganizationChunkTopic;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailVisibilitiesForOrganizationTopic;
use Oro\Bundle\EmailBundle\Async\UpdateEmailVisibilitiesForOrganizationProcessor;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;

class UpdateEmailVisibilitiesForOrganizationProcessorTest extends OrmTestCase
{
    use MessageQueueExtension;

    private const CHUNK_SIZE = 10000;
    private const JOB_NAME = 'oro:email:update-visibilities:emails';

    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var EntityManagerInterface */
    private $em;

    /** @var UpdateEmailVisibilitiesForOrganizationProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(
            new AnnotationReader(),
            [dirname((new \ReflectionClass(Email::class))->getFileName())]
        ));

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Email::class)
            ->willReturn($this->em);

        $this->processor = new UpdateEmailVisibilitiesForOrganizationProcessor(
            $doctrine,
            self::getMessageProducer(),
            $this->jobRunner
        );
    }

    private function getMessage(array $body, string $messageId): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn($body);
        $message->expects(self::any())
            ->method('getMessageId')
            ->willReturn($messageId);
        $message->expects(self::any())
            ->method('getProperties')
            ->willReturn([]);

        return $message;
    }

    private function expectsRunUnique(MessageInterface $message): void
    {
        $job = new Job();
        $job->setName(self::JOB_NAME);
        $this->jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->with($message)
            ->willReturnCallback(function ($message, $runCallback) use ($job) {
                return $runCallback($this->jobRunner, $job);
            });
    }

    private function addCountQueryExpectation(int $organizationId, int $count): void
    {
        $this->addQueryExpectation(
            'SELECT count(o0_.id) AS sclr_0'
            . ' FROM oro_email o0_'
            . ' INNER JOIN oro_email_user o1_ ON o0_.id = o1_.email_id'
            . ' WHERE o1_.organization_id = ?',
            [['sclr_0' => $count]],
            [1 => $organizationId],
            [1 => \PDO::PARAM_INT]
        );
    }

    private function addDataQueryExpectation(int $organizationId, array $data, int $offset = null): void
    {
        $sql = 'SELECT o0_.id AS id_0'
            . ' FROM oro_email o0_'
            . ' INNER JOIN oro_email_user o1_ ON o0_.id = o1_.email_id'
            . ' WHERE o1_.organization_id = ?'
            . ' ORDER BY o0_.id ASC'
            . ' LIMIT ' . self::CHUNK_SIZE;
        if (null !== $offset) {
            $sql .= ' OFFSET ' . $offset;
        }
        $this->addQueryExpectation(
            $sql,
            $data,
            [1 => $organizationId],
            [1 => \PDO::PARAM_INT]
        );
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [UpdateEmailVisibilitiesForOrganizationTopic::getName()],
            UpdateEmailVisibilitiesForOrganizationProcessor::getSubscribedTopics()
        );
    }

    public function testProcessWhenEmailsNotFound(): void
    {
        $organizationId = 1;

        $messageId = 'test_message';
        $message = $this->getMessage(['organizationId' => $organizationId], $messageId);

        $this->addCountQueryExpectation($organizationId, 0);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $this->expectsRunUnique($message);
        $this->jobRunner->expects(self::never())
            ->method('createDelayed');

        $result = $this->processor->process(
            $message,
            $this->createMock(SessionInterface::class)
        );
        self::assertEquals(MessageProcessorInterface::ACK, $result);

        self::assertMessagesEmpty(UpdateEmailVisibilitiesForOrganizationChunkTopic::getName());
    }

    public function testProcessForOneChunk(): void
    {
        $organizationId = 1;
        $jobName = self::JOB_NAME;
        $firstEmailId = 10;

        $messageId = 'test_message';
        $message = $this->getMessage(['organizationId' => $organizationId], $messageId);

        $jobId = 123;
        $job = new Job();

        $this->addCountQueryExpectation($organizationId, 1);
        $this->addDataQueryExpectation($organizationId, [['id_0' => $firstEmailId]]);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $this->expectsRunUnique($message);
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->with(sprintf('%s:1', $jobName))
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
            UpdateEmailVisibilitiesForOrganizationChunkTopic::getName(),
            ['jobId' => $jobId, 'organizationId' => $organizationId, 'firstEmailId' => $firstEmailId]
        );
    }

    public function testProcessForSeveralChunk(): void
    {
        $organizationId = 1;
        $jobName = self::JOB_NAME;
        $firstEmailId = 10;

        $messageId = 'test_message';
        $message = $this->getMessage(['organizationId' => $organizationId], $messageId);

        $job1Id = 123;
        $job2Id = 124;
        $job1 = new Job();
        $job2 = new Job();

        $chunk1Data = [];
        $chunk2Data = [];
        $emailId = $firstEmailId;
        for ($i = 0; $i <= self::CHUNK_SIZE + 2; $i++) {
            if (count($chunk1Data) < self::CHUNK_SIZE) {
                $chunk1Data[] = ['id_0' => $emailId];
            } else {
                $chunk2Data[] = ['id_0' => $emailId];
            }
            $emailId++;
        }

        $this->addCountQueryExpectation($organizationId, count($chunk1Data) + count($chunk2Data));
        $this->addDataQueryExpectation($organizationId, $chunk1Data);
        $this->addDataQueryExpectation($organizationId, $chunk2Data, self::CHUNK_SIZE);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $this->expectsRunUnique($message);
        $this->jobRunner->expects(self::exactly(2))
            ->method('createDelayed')
            ->withConsecutive([sprintf('%s:1', $jobName)], [sprintf('%s:2', $jobName)])
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function ($name, $startCallback) use ($job1, $job1Id) {
                    $job1->setId($job1Id);

                    return $startCallback($this->jobRunner, $job1);
                }),
                new ReturnCallback(function ($name, $startCallback) use ($job2, $job2Id) {
                    $job2->setId($job2Id);

                    return $startCallback($this->jobRunner, $job2);
                }),
            );

        $result = $this->processor->process(
            $message,
            $this->createMock(SessionInterface::class)
        );
        self::assertEquals(MessageProcessorInterface::ACK, $result);

        self::assertMessagesSent(
            UpdateEmailVisibilitiesForOrganizationChunkTopic::getName(),
            [
                [
                    'jobId'          => $job1Id,
                    'organizationId' => $organizationId,
                    'firstEmailId'   => $firstEmailId,
                    'lastEmailId'    => $firstEmailId + self::CHUNK_SIZE - 1
                ],
                [
                    'jobId'          => $job2Id,
                    'organizationId' => $organizationId,
                    'firstEmailId'   => $firstEmailId + self::CHUNK_SIZE
                ]
            ]
        );
    }

    public function testProcessWhenNumberOfFoundEmailsEqualsToChunkSize(): void
    {
        $organizationId = 1;
        $jobName = self::JOB_NAME;
        $firstEmailId = 10;

        $messageId = 'test_message';
        $message = $this->getMessage(['organizationId' => $organizationId], $messageId);

        $jobId = 123;
        $job = new Job();

        $chunkData = [];
        $emailId = $firstEmailId;
        for ($i = 0; $i < self::CHUNK_SIZE; $i++) {
            $chunkData[] = ['id_0' => $emailId];
            $emailId++;
        }

        $this->addCountQueryExpectation($organizationId, count($chunkData));
        $this->addDataQueryExpectation($organizationId, $chunkData);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $this->expectsRunUnique($message);
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->with(sprintf('%s:1', $jobName))
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
            UpdateEmailVisibilitiesForOrganizationChunkTopic::getName(),
            ['jobId' => $jobId, 'organizationId' => $organizationId, 'firstEmailId' => $firstEmailId]
        );
    }
}
