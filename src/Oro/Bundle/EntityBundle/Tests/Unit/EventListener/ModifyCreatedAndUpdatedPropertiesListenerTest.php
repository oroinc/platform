<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedByAwareInterface;
use Oro\Bundle\EntityBundle\EventListener\ModifyCreatedAndUpdatedPropertiesListener;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ModifyCreatedAndUpdatedPropertiesListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ModifyCreatedAndUpdatedPropertiesListener */
    protected $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenStorageInterface */
    protected $tokenStorage;

    protected function setUp()
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->listener = new ModifyCreatedAndUpdatedPropertiesListener($this->tokenStorage);
    }

    public function testOptionalListenerInterfaceImplementation()
    {
        $args = $this->createMock(OnFlushEventArgs::class);
        $args->expects($this->never())
            ->method('getEntityManager');

        $this->listener->setEnabled(false);
        $this->listener->onFlush($args);
    }

    /**
     * @dataProvider userDataProvider
     *
     * @param object  $user
     * @param boolean $expectedCallSetUpdatedBy
     */
    public function testModifyCreatedAndUpdatedPropertiesForNewEntity($user, $expectedCallSetUpdatedBy)
    {
        $datesAwareEntity = $this->createMock(DatesAwareInterface::class);

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

        $alreadyUpdatedDatesAwareEntity = $this->createMock(DatesAwareInterface::class);
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

        $currentToken = $this->createMock(TokenInterface::class);
        $currentToken->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($currentToken);

        $updatedByAwareEntity = $this->createMock(UpdatedByAwareInterface::class);
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
        $alreadyUpdatedUpdatedByAwareEntity = $this->createMock(UpdatedByAwareInterface::class);
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
        $args = $this->createArgsMock($countOfRecompute, $scheduled, []);

        $this->listener->onFlush($args);
    }

    /**
     * @dataProvider userDataProvider
     *
     * @param object  $user
     * @param boolean $expectedCallSetUpdatedBy
     */
    public function testModifyCreatedAndUpdatedPropertiesForExistingEntity($user, $expectedCallSetUpdatedBy)
    {
        $datesAwareEntity = $this->createMock(DatesAwareInterface::class);

        $datesAwareEntity->expects($this->once())
            ->method('isUpdatedAtSet')
            ->willReturn(false);
        $datesAwareEntity->expects($this->once())
            ->method('setUpdatedAt')
            ->with($this->equalTo(new \DateTime(), 1));

        $alreadyUpdatedDatesAwareEntity = $this->createMock(DatesAwareInterface::class);
        $alreadyUpdatedDatesAwareEntity->expects($this->once())
            ->method('isUpdatedAtSet')
            ->willReturn(true);
        $alreadyUpdatedDatesAwareEntity->expects($this->never())
            ->method('setUpdatedAt');

        $currentToken = $this->createMock(TokenInterface::class);
        $currentToken->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($currentToken);

        $updatedByAwareEntity = $this->createMock(UpdatedByAwareInterface::class);
        $updatedByAwareEntity->expects($this->once())
            ->method('isUpdatedBySet')
            ->willReturn(false);
        $updatedByAwareEntity->expects($this->exactly((int)$expectedCallSetUpdatedBy))
            ->method('setUpdatedBy')
            ->with($user);

        $alreadyUpdatedUpdatedByAwareEntity = $this->createMock(UpdatedByAwareInterface::class);
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
        $args = $this->createArgsMock($countOfRecompute, [], $scheduled);

        $this->listener->onFlush($args);
    }

    /**
     * @return array
     */
    public function userDataProvider()
    {
        return [
            'realUser'    => [
                'user'                     => $this->createMock(User::class),
                'expectedCallSetUpdatedBy' => true
            ],
            'anotherUser' => [
                'user'                     => new \stdClass(),
                'expectedCallSetUpdatedBy' => false
            ],
        ];
    }

    /**
     * @param int      $countOfRecompute
     * @param object[] $scheduledForInsert
     * @param object[] $scheduledForUpdate
     *
     * @return OnFlushEventArgs
     */
    protected function createArgsMock($countOfRecompute, array $scheduledForInsert, array $scheduledForUpdate)
    {
        $entityManager = $this->createMock(EntityManager::class);
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $metadataStub = $this->createMock(ClassMetadata::class);

        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadataStub);
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

        return new OnFlushEventArgs($entityManager);
    }
}
