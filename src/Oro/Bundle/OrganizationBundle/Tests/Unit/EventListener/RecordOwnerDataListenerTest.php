<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\OrganizationBundle\EventListener\RecordOwnerDataListener;
use Oro\Bundle\OrganizationBundle\Ownership\EntityOwnershipAssociationsSetter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RecordOwnerDataListenerTest extends TestCase
{
    private EntityOwnershipAssociationsSetter&MockObject $entityOwnershipAssociationsSetter;
    private RecordOwnerDataListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityOwnershipAssociationsSetter = $this->createMock(EntityOwnershipAssociationsSetter::class);

        $this->listener = new RecordOwnerDataListener($this->entityOwnershipAssociationsSetter);
    }

    public function testPrePersist(): void
    {
        $entity = new \stdClass();

        $this->entityOwnershipAssociationsSetter->expects(self::once())
            ->method('setOwnershipAssociations')
            ->with(self::identicalTo($entity));

        $this->listener->prePersist(
            new LifecycleEventArgs($entity, $this->createMock(EntityManagerInterface::class))
        );
    }
}
