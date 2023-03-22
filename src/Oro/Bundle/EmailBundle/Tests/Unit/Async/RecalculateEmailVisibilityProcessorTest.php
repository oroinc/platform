<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\RecalculateEmailVisibilityProcessor;
use Oro\Bundle\EmailBundle\Async\Topic\RecalculateEmailVisibilityChunkTopic;
use Oro\Bundle\EmailBundle\Async\Topic\RecalculateEmailVisibilityTopic;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;

class RecalculateEmailVisibilityProcessorTest extends OrmTestCase
{
    private const CHUNK_SIZE = 500;
    private const JOB_NAME = 'oro.email.recalculate_email_visibility';

    use MessageQueueExtension;

    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var EntityManagerInterface */
    private $em;

    /** @var RecalculateEmailVisibilityProcessor */
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
            ->method('getRepository')
            ->with(Email::class)
            ->willReturn(new EmailRepository($this->em, $this->em->getClassMetadata(Email::class)));

        $this->processor = new RecalculateEmailVisibilityProcessor(
            $doctrine,
            $this->jobRunner,
            self::getMessageProducer()
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

    private function addCountQueryExpectation(string $email, int $count): void
    {
        $this->addQueryExpectation(
            'SELECT DISTINCT count(DISTINCT o0_.id) AS sclr_0'
            . ' FROM oro_email_user o0_'
            . ' INNER JOIN oro_email o1_ ON o0_.email_id = o1_.id'
            . ' INNER JOIN oro_email_recipient o2_ ON o1_.id = o2_.email_id'
            . ' INNER JOIN EmailAddress e3_ ON o2_.email_address_id = e3_.id'
            . ' INNER JOIN EmailAddress e4_ ON o1_.from_email_address_id = e4_.id'
            . ' WHERE e4_.email = ? OR e3_.email = ?',
            [['sclr_0' => $count]],
            [1 => $email, 2 => $email],
            [1 => \PDO::PARAM_STR, 2 => \PDO::PARAM_STR]
        );
    }

    private function addDataQueryExpectation(string $email, array $data, int $offset = null): void
    {
        $sql = 'SELECT DISTINCT o0_.id AS id_0'
            . ' FROM oro_email_user o0_'
            . ' INNER JOIN oro_email o1_ ON o0_.email_id = o1_.id'
            . ' INNER JOIN oro_email_recipient o2_ ON o1_.id = o2_.email_id'
            . ' INNER JOIN EmailAddress e3_ ON o2_.email_address_id = e3_.id'
            . ' INNER JOIN EmailAddress e4_ ON o1_.from_email_address_id = e4_.id'
            . ' WHERE e4_.email = ? OR e3_.email = ?'
            . ' ORDER BY o0_.id ASC'
            . ' LIMIT ' . self::CHUNK_SIZE;
        if (null !== $offset) {
            $sql .= ' OFFSET ' . $offset;
        }
        $this->addQueryExpectation(
            $sql,
            $data,
            [1 => $email, 2 => $email],
            [1 => \PDO::PARAM_STR, 2 => \PDO::PARAM_STR]
        );
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [RecalculateEmailVisibilityTopic::getName()],
            RecalculateEmailVisibilityProcessor::getSubscribedTopics()
        );
    }

    public function testProcessWhenEmailsNotFound(): void
    {
        $email = 'test@test.com';

        $messageId = 'test_message';
        $message = $this->getMessage(['email' => $email], $messageId);

        $this->expectsRunUnique($message);
        $this->jobRunner->expects(self::never())
            ->method('createDelayed');

        $this->addCountQueryExpectation($email, 0);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $result = $this->processor->process(
            $message,
            $this->createMock(SessionInterface::class)
        );

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertMessagesEmpty(RecalculateEmailVisibilityChunkTopic::getName());
    }

    public function testProcessForOneChunk(): void
    {
        $email = 'test1@test.com';
        $jobName = self::JOB_NAME;

        $messageId = 'test_message';
        $message = $this->getMessage(['email' => $email], $messageId);

        $jobId = 123;
        $job = new Job();

        $this->addCountQueryExpectation($email, 1);
        $this->addDataQueryExpectation($email, [['id_0' => 135]]);
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
            RecalculateEmailVisibilityChunkTopic::getName(),
            [
                'jobId' => $jobId,
                'ids'   => [135]
            ]
        );
    }

    public function testProcessForSeveralChunk(): void
    {
        $email = 'test2@test.com';
        $jobName = self::JOB_NAME;

        $messageId = 'test_message';
        $message = $this->getMessage(['email' => $email], $messageId);

        $job1Id = 123;
        $job2Id = 124;
        $job1 = new Job();
        $job2 = new Job();

        $chunk1Data = [];
        $expected1Data = [];
        $chunk2Data = [];
        $expected2Data = [];
        for ($i = 0; $i <= self::CHUNK_SIZE + 10; $i++) {
            if (count($chunk1Data) < self::CHUNK_SIZE) {
                $expected1Data[] = $i;
                $chunk1Data[] = ['id_0' => $i];
            } else {
                $expected2Data[] = $i;
                $chunk2Data[] = ['id_0' => $i];
            }
        }

        $this->addCountQueryExpectation($email, count($chunk1Data) + count($chunk2Data));
        $this->addDataQueryExpectation($email, $chunk1Data);
        $this->addDataQueryExpectation($email, $chunk2Data, self::CHUNK_SIZE);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $this->expectsRunUnique($message, $email);
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
            RecalculateEmailVisibilityChunkTopic::getName(),
            [
                [
                    'jobId'=> $job1Id,
                    'ids'  => $expected1Data
                ],
                [
                    'jobId'=> $job2Id,
                    'ids'  => $expected2Data,
                ]
            ]
        );
    }
}
