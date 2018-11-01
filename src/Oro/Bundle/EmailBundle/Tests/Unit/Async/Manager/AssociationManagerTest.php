<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async\Manager;

use Doctrine\ORM\Query;
use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailManager;
use Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class AssociationManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AssociationManager */
    private $associationManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EmailActivityManager */
    private $emailActivityManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EmailManager */
    private $emailManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EmailOwnersProvider */
    private $emailOwnersProvider;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailActivityManager = $this->
        getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailOwnersProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailManager = self::getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailManager')
            ->disableOriginalConstructor()
            ->getMock();

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
     *
     * @param $ids
     * @param $targetClass
     * @param $targetId
     * @param $expectedCountAssociation
     * @param $addAssociation
     */
    public function testProcessAddAssociation($ids, $targetClass, $targetId, $expectedCountAssociation, $addAssociation)
    {
        $owner = new \stdClass();
        $entityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()->getMock();
        $entityManager= $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();

        $entityRepository->expects(self::once())->method('find')->willReturn($owner);
        $entityManager->expects(self::once())->method('flush');
        $this->doctrineHelper->expects(self::once())->method('getEntityRepository')->willReturn($entityRepository);

        $emails = [];
        for ($i = 0, $max = count($ids); $i < $max; $i++) {
            $emails[] = new Email();
        }
        $this->emailManager->expects(self::once())->method('findEmailsByIds')->willReturn($emails);

        $this->emailActivityManager->expects(self::exactly(count($ids)))
            ->method('addAssociation')
            ->willReturn($addAssociation);

        $this->doctrineHelper->expects(self::once())->method('getEntityManager')->willReturn($entityManager);

        $countNewAssociations = $this->associationManager->processAddAssociation($ids, $targetClass, $targetId);
        self::assertEquals($expectedCountAssociation, $countNewAssociations);
    }

    public function processAddAssociationDataProvider()
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
