<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailManager;
use Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AssociationManagerTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private ActivityManager&MockObject $activityManager;
    private EmailOwnersProvider&MockObject $emailOwnersProvider;
    private EmailManager&MockObject $emailManager;
    private MessageProducerInterface&MockObject $producer;
    private AssociationManager $associationManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->activityManager = $this->createMock(ActivityManager::class);
        $this->emailOwnersProvider = $this->createMock(EmailOwnersProvider::class);
        $this->emailManager = $this->createMock(EmailManager::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);

        $this->associationManager = new AssociationManager(
            $this->doctrineHelper,
            $this->activityManager,
            $this->emailOwnersProvider,
            $this->emailManager,
            $this->producer
        );
    }

    /**
     * @dataProvider processAddAssociationDataProvider
     */
    public function testProcessAddAssociation(
        array $ids,
        string $targetClass,
        int $targetId,
        int $expectedCountAssociation,
        bool $addAssociation
    ): void {
        $owner = new \stdClass();
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityRepository->expects(self::once())
            ->method('find')
            ->willReturn($owner);
        $entityManager->expects(self::once())
            ->method('flush');
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->willReturn($entityRepository);

        $emails = [];
        for ($i = 0, $max = count($ids); $i < $max; $i++) {
            $emails[] = new Email();
        }
        $this->emailManager->expects(self::once())
            ->method('findEmailsByIds')
            ->willReturn($emails);

        $this->activityManager->expects(self::exactly(count($ids)))
            ->method('addActivityTarget')
            ->willReturn($addAssociation);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with(Email::class)
            ->willReturn($entityManager);

        $countNewAssociations = $this->associationManager->processAddAssociation($ids, $targetClass, $targetId);
        self::assertEquals($expectedCountAssociation, $countNewAssociations);
    }

    public function processAddAssociationDataProvider(): array
    {
        return [
            [
                'ids' => [1, 2, 3],
                'targetClass' => 'TestClass',
                'targetId' => 1,
                'expectedCountAssociation' => 3,
                'addAssociation' => true
            ],
            [
                'ids' => [1, 2, 3],
                'targetClass' => 'TestClass',
                'targetId' => 1,
                'expectedCountAssociation' => 0,
                'addAssociation' => false
            ],
            [
                'ids' => [],
                'targetClass' => 'TestClass',
                'targetId' => 1,
                'expectedCountAssociation' => 0,
                'addAssociation' => true
            ],
        ];
    }
}
