<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\EntityBundle\EventListener\ModifyCreatedAndUpdatedPropertiesListener;

class ModifyCreatedAndUpdatedPropertiesListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ModifyCreatedAndUpdatedPropertiesListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    protected function setUp()
    {
        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');

        $this->listener = new ModifyCreatedAndUpdatedPropertiesListener($this->tokenStorage);
    }

    public function testOptionalListenerInterfaceImplementation()
    {
        $this->listener->setEnabled(false);
        /** @var OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $args->expects($this->never())
            ->method('getEntityManager');
        $this->listener->onFlush($args);
    }

    /**
     * @dataProvider userDataProvider
     * @param object $user
     * @param boolean $expectedCallSetUpdatedBy
     */
    public function testModifyCreatedAndUpdatedPropertiesForNewEntity($user, $expectedCallSetUpdatedBy)
    {
        $datesAwareEntity = $this->getMock('Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface');

        $datesAwareEntity->expects($this->once())
            ->method('getCreatedAt');
        $datesAwareEntity->expects($this->once())
            ->method('setCreatedAt')
            ->with($this->equalTo(new \DateTime(), 1));
        $datesAwareEntity->expects($this->once())
            ->method('isUpdatedAtSet')
            ->willReturn(false);
        $datesAwareEntity->expects($this->once())
            ->method('setUpdatedAt')
            ->with($this->equalTo(new \DateTime(), 1));

        $alreadyUpdatedDatesAwareEntity = $this->getMock('Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface');
        $alreadyUpdatedDatesAwareEntity->expects($this->once())
            ->method('getCreatedAt')
            ->willReturn(new \DateTime());
        $alreadyUpdatedDatesAwareEntity->expects($this->never())
            ->method('setCreatedAt');
        $alreadyUpdatedDatesAwareEntity->expects($this->once())
            ->method('isUpdatedAtSet')
            ->willReturn(true);
        $alreadyUpdatedDatesAwareEntity->expects($this->never())
            ->method('setUpdatedAt');

        $currentToken = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $currentToken->expects($this->exactly(4))
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->exactly(4))
            ->method('getToken')
            ->willReturn($currentToken);

        $updatedByAwareEntity = $this->getMock('Oro\Bundle\EntityBundle\EntityProperty\UpdatedByAwareInterface');
        $updatedByAwareEntity->expects($this->once())
            ->method('isUpdatedBySet')
            ->willReturn(false);
        if ($expectedCallSetUpdatedBy) {
            $updatedByAwareEntity->expects($this->once())
                ->method('setUpdatedBy')
                ->with($user);
        } else {
            $updatedByAwareEntity->expects($this->never())
                ->method('setUpdatedBy');
        }
        $alreadyUpdatedUpdatedByAwareEntity = $this->getMock(
            'Oro\Bundle\EntityBundle\EntityProperty\UpdatedByAwareInterface'
        );
        $alreadyUpdatedUpdatedByAwareEntity->expects($this->once())
            ->method('isUpdatedBySet')
            ->willReturn(true);
        $alreadyUpdatedUpdatedByAwareEntity->expects($this->never())
            ->method('setUpdatedBy');

        $scheduled = [
            $datesAwareEntity,
            $updatedByAwareEntity,
            $alreadyUpdatedDatesAwareEntity,
            $alreadyUpdatedUpdatedByAwareEntity
        ];
        $countOfRecompute = $expectedCallSetUpdatedBy ? 2 : 1;
        $this->createArgsMock($countOfRecompute, $scheduled);
    }

    /**
     * @dataProvider userDataProvider
     * @param object $user
     * @param boolean $expectedCallSetUpdatedBy
     */
    public function testModifyCreatedAndUpdatedPropertiesForExistingEntity($user, $expectedCallSetUpdatedBy)
    {
        $datesAwareEntity = $this->getMock('Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface');

        $datesAwareEntity->expects($this->once())
            ->method('isUpdatedAtSet')
            ->willReturn(false);
        $datesAwareEntity->expects($this->once())
            ->method('setUpdatedAt')
            ->with($this->equalTo(new \DateTime(), 1));

        $alreadyUpdatedDatesAwareEntity = $this->getMock('Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface');
        $alreadyUpdatedDatesAwareEntity->expects($this->once())
            ->method('isUpdatedAtSet')
            ->willReturn(true);
        $alreadyUpdatedDatesAwareEntity->expects($this->never())
            ->method('setUpdatedAt');

        $currentToken = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $currentToken->expects($this->exactly(4))
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->exactly(4))
            ->method('getToken')
            ->willReturn($currentToken);

        $updatedByAwareEntity = $this->getMock('Oro\Bundle\EntityBundle\EntityProperty\UpdatedByAwareInterface');
        $updatedByAwareEntity->expects($this->once())
            ->method('isUpdatedBySet')
            ->willReturn(false);
        $updatedByAwareEntity->expects($this->exactly((int)$expectedCallSetUpdatedBy))
            ->method('setUpdatedBy')
            ->with($user);

        $alreadyUpdatedUpdatedByAwareEntity = $this->getMock(
            'Oro\Bundle\EntityBundle\EntityProperty\UpdatedByAwareInterface'
        );
        $alreadyUpdatedUpdatedByAwareEntity->expects($this->once())
            ->method('isUpdatedBySet')
            ->willReturn(true);
        $alreadyUpdatedUpdatedByAwareEntity->expects($this->never())
            ->method('setUpdatedBy');

        $scheduled = [
            $datesAwareEntity,
            $updatedByAwareEntity,
            $alreadyUpdatedDatesAwareEntity,
            $alreadyUpdatedUpdatedByAwareEntity
        ];
        $countOfRecompute = $expectedCallSetUpdatedBy ? 2 : 1;
        $this->createArgsMock($countOfRecompute, [], $scheduled);
    }

    /**
     * @return array
     */
    public function userDataProvider()
    {
        return [
            'realUser' => [
                'user' => $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
                    ->disableOriginalConstructor()
                    ->getMock(),
                'expectedCallSetUpdatedBy' => true
            ],
            'anotherUser' => [
                'user' => new \stdClass(),
                'expectedCallSetUpdatedBy' => false
            ],
        ];
    }

    /**
     * @param int $countOfRecompute
     * @param array $scheduledForInsert
     * @param array $scheduledForUpdate
     */
    protected function createArgsMock($countOfRecompute, array $scheduledForInsert, array $scheduledForUpdate = [])
    {
        /** @var OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $metadataStub = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadataStub);

        $args->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn($scheduledForInsert);

        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn($scheduledForUpdate);

        $unitOfWork->expects($this->exactly($countOfRecompute))
            ->method('recomputeSingleEntityChangeSet');

        $this->listener->onFlush($args);
    }
}
