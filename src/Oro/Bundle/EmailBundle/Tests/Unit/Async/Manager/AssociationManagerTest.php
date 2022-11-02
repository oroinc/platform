<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailManager;
use Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class AssociationManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EmailActivityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailActivityManager;

    /** @var EmailOwnersProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $emailOwnersProvider;

    /** @var EmailManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailManager;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $producer;

    /** @var AssociationManager */
    private $associationManager;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->emailActivityManager = $this->createMock(EmailActivityManager::class);
        $this->emailOwnersProvider = $this->createMock(EmailOwnersProvider::class);
        $this->emailManager = $this->createMock(EmailManager::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);

        $this->associationManager = new AssociationManager(
            $this->doctrineHelper,
            $this->emailActivityManager,
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
    ) {
        $owner = new \stdClass();
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityManager= $this->createMock(EntityManager::class);

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

        $this->emailActivityManager->expects(self::exactly(count($ids)))
            ->method('addAssociation')
            ->willReturn($addAssociation);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
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
