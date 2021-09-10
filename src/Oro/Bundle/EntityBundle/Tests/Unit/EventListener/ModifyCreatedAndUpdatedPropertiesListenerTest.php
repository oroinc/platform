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
    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var ModifyCreatedAndUpdatedPropertiesListener */
    private $listener;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->listener = new ModifyCreatedAndUpdatedPropertiesListener($this->tokenStorage);
    }

    /**
     * @dataProvider userDataProvider
     */
    public function testModifyCreatedAndUpdatedPropertiesForNewEntity(object $user, bool $expectedCallSetUpdatedBy)
    {
        $datesAwareEntity = $this->createMock(DatesAwareInterface::class);

        $datesAwareEntity->expects(self::once())
            ->method('getCreatedAt');
        $datesAwareEntity->expects(self::once())
            ->method('setCreatedAt')
            ->with(self::equalToWithDelta(new \DateTime(), 1.0));
        $datesAwareEntity->expects(self::once())
            ->method('isUpdatedAtSet')
            ->willReturn(false);
        $datesAwareEntity->expects(self::once())
            ->method('setUpdatedAt')
            ->with(self::equalToWithDelta(new \DateTime(), 1.0));

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

        $args = $this->getOnFlushEventArgs($countOfRecompute, $scheduled, []);
        $this->listener->onFlush($args);
    }

    /**
     * @dataProvider userDataProvider
     */
    public function testModifyCreatedAndUpdatedPropertiesForExistingEntity(object $user, bool $expectedCallSetUpdatedBy)
    {
        $datesAwareEntity = $this->createMock(DatesAwareInterface::class);

        $datesAwareEntity->expects(self::once())
            ->method('isUpdatedAtSet')
            ->willReturn(false);
        $datesAwareEntity->expects(self::once())
            ->method('setUpdatedAt')
            ->with(self::equalToWithDelta(new \DateTime(), 1.0));

        $alreadyUpdatedDatesAwareEntity = $this->createMock(DatesAwareInterface::class);
        $alreadyUpdatedDatesAwareEntity->expects(self::once())
            ->method('isUpdatedAtSet')
            ->willReturn(true);
        $alreadyUpdatedDatesAwareEntity->expects(self::never())
            ->method('setUpdatedAt');

        $currentToken = $this->createMock(TokenInterface::class);
        $currentToken->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($currentToken);

        $updatedByAwareEntity = $this->createMock(UpdatedByAwareInterface::class);
        $updatedByAwareEntity->expects(self::once())
            ->method('isUpdatedBySet')
            ->willReturn(false);
        $updatedByAwareEntity->expects(self::exactly((int)$expectedCallSetUpdatedBy))
            ->method('setUpdatedBy')
            ->with($user);

        $alreadyUpdatedUpdatedByAwareEntity = $this->createMock(UpdatedByAwareInterface::class);
        $alreadyUpdatedUpdatedByAwareEntity->expects(self::once())
            ->method('isUpdatedBySet')
            ->willReturn(true);
        $alreadyUpdatedUpdatedByAwareEntity->expects(self::never())
            ->method('setUpdatedBy');

        $scheduled = [
            $datesAwareEntity,
            $updatedByAwareEntity,
            $alreadyUpdatedDatesAwareEntity,
            $alreadyUpdatedUpdatedByAwareEntity
        ];
        $countOfRecompute = $expectedCallSetUpdatedBy ? 2 : 1;

        $args = $this->getOnFlushEventArgs($countOfRecompute, [], $scheduled);
        $this->listener->onFlush($args);
    }

    public function userDataProvider(): array
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

    private function getOnFlushEventArgs(
        int $countOfRecompute,
        array $scheduledForInsert,
        array $scheduledForUpdate
    ): OnFlushEventArgs {
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
