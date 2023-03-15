<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailVisibilitiesForOrganizationChunkTopic;
use Oro\Bundle\EmailBundle\Async\UpdateEmailVisibilitiesForOrganizationChunkProcessor;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\ORM\Mocks\EntityManagerMock;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use PHPUnit\Framework\Constraint\StringEndsWith;

class UpdateEmailVisibilitiesForOrganizationChunkProcessorTest extends OrmTestCase
{
    private const BUFFER_SIZE = 100;

    /** @var EmailAddressVisibilityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailAddressVisibilityManager;

    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var EntityManagerMock */
    private $em;

    /** @var UpdateEmailVisibilitiesForOrganizationChunkProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->emailAddressVisibilityManager = $this->createMock(EmailAddressVisibilityManager::class);
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

        $this->processor = new UpdateEmailVisibilitiesForOrganizationChunkProcessor(
            $doctrine,
            $this->emailAddressVisibilityManager,
            $this->jobRunner
        );
    }

    private function getMessage(array $body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn($body);

        return $message;
    }

    private function expectsRunDelayed(int $jobId): void
    {
        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->with($jobId)
            ->willReturnCallback(function ($jobId, $runCallback) {
                return $runCallback($this->jobRunner, new Job());
            });
    }

    private function addCountQueryExpectation(
        int $organizationId,
        int $firstEmailId,
        ?int $lastEmailId,
        int $count
    ): void {
        $sql = 'SELECT count(o0_.id) AS sclr_0'
            . ' FROM oro_email_user o0_'
            . ' INNER JOIN oro_organization o1_ ON o0_.organization_id = o1_.id'
            . ' INNER JOIN oro_email o2_ ON o0_.email_id = o2_.id'
            . ' LEFT JOIN EmailAddress e3_ ON o2_.from_email_address_id = e3_.id'
            . ' LEFT JOIN oro_email_recipient o4_ ON o2_.id = o4_.email_id'
            . ' LEFT JOIN EmailAddress e5_ ON o4_.email_address_id = e5_.id';
        $params = [1 => $organizationId, 2 => $firstEmailId];
        $types = [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT];
        if (null === $lastEmailId) {
            $sql .= ' WHERE o1_.id = ? AND o2_.id >= ?';
        } else {
            $sql .= ' WHERE (o1_.id = ? AND o2_.id >= ?) AND o2_.id <= ?';
            $params[3] = $lastEmailId;
            $types[3] = \PDO::PARAM_INT;
        }
        $this->addQueryExpectation(
            $sql,
            [['sclr_0' => $count]],
            $params,
            $types
        );
    }

    private function addDataQueryExpectation(
        int $organizationId,
        int $firstEmailId,
        ?int $lastEmailId,
        array $data
    ): void {
        $sql = ' FROM oro_email_user o0_'
            . ' INNER JOIN oro_organization o1_ ON o0_.organization_id = o1_.id'
            . ' INNER JOIN oro_email o2_ ON o0_.email_id = o2_.id'
            . ' LEFT JOIN EmailAddress e3_ ON o2_.from_email_address_id = e3_.id'
            . ' LEFT JOIN oro_email_recipient o4_ ON o2_.id = o4_.email_id'
            . ' LEFT JOIN EmailAddress e5_ ON o4_.email_address_id = e5_.id';
        $params = [1 => $organizationId, 2 => $firstEmailId];
        $types = [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT];
        if (null === $lastEmailId) {
            $sql .= ' WHERE o1_.id = ? AND o2_.id >= ?';
        } else {
            $sql .= ' WHERE (o1_.id = ? AND o2_.id >= ?) AND o2_.id <= ?';
            $params[3] = $lastEmailId;
            $types[3] = \PDO::PARAM_INT;
        }
        $sql .= ' ORDER BY o2_.id ASC, o0_.id ASC'
            . ' LIMIT ' . self::BUFFER_SIZE;
        $this->addQueryExpectation(
            new StringEndsWith($sql),
            $data,
            $params,
            $types
        );
    }

    private function expectsProcessEmailUserVisibility(array $emailUserIds): void
    {
        $expectations = [];
        foreach ($emailUserIds as $emailUserId) {
            $expectations[] = [
                self::callback(function ($emailUser) use ($emailUserId) {
                    self::assertInstanceOf(EmailUser::class, $emailUser);
                    self::assertSame($emailUserId, $emailUser->getId());

                    return true;
                })
            ];
        }
        $this->emailAddressVisibilityManager->expects(self::exactly(count($emailUserIds)))
            ->method('processEmailUserVisibility')
            ->withConsecutive(...$expectations);

        // EntityManager::flush() expectation
        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->setConstructorArgs([$this->em])
            ->onlyMethods(['commit'])
            ->getMock();
        $this->em->setUnitOfWork($uow);
        $uow->expects(self::once())
            ->method('commit');
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [UpdateEmailVisibilitiesForOrganizationChunkTopic::getName()],
            UpdateEmailVisibilitiesForOrganizationChunkProcessor::getSubscribedTopics()
        );
    }

    public function testProcess(): void
    {
        $jobId = 123;
        $organizationId = 1;
        $firstEmailId = 10;
        $lastEmailId = 19;

        $message = $this->getMessage([
            'jobId'          => $jobId,
            'organizationId' => $organizationId,
            'firstEmailId'   => $firstEmailId,
            'lastEmailId'    => $lastEmailId
        ]);

        $emailUser1Id = 201;
        $emailUser2Id = 202;

        $this->addCountQueryExpectation($organizationId, $firstEmailId, $lastEmailId, 2);
        $this->addDataQueryExpectation(
            $organizationId,
            $firstEmailId,
            $lastEmailId,
            [['id_0' => $emailUser1Id], ['id_0' => $emailUser2Id]]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $this->expectsProcessEmailUserVisibility([$emailUser1Id, $emailUser2Id]);

        $this->expectsRunDelayed($jobId);

        $result = $this->processor->process(
            $message,
            $this->createMock(SessionInterface::class)
        );
        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWithoutLastEmailId(): void
    {
        $jobId = 123;
        $organizationId = 1;
        $firstEmailId = 10;

        $message = $this->getMessage([
            'jobId'          => $jobId,
            'organizationId' => $organizationId,
            'firstEmailId'   => $firstEmailId
        ]);

        $emailUser1Id = 201;
        $emailUser2Id = 202;

        $this->addCountQueryExpectation($organizationId, $firstEmailId, null, 2);
        $this->addDataQueryExpectation(
            $organizationId,
            $firstEmailId,
            null,
            [['id_0' => $emailUser1Id], ['id_0' => $emailUser2Id]]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $this->expectsProcessEmailUserVisibility([$emailUser1Id, $emailUser2Id]);

        $this->expectsRunDelayed($jobId);

        $result = $this->processor->process(
            $message,
            $this->createMock(SessionInterface::class)
        );
        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }
}
