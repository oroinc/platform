<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\OrganizationBundle\EventListener\RecordOwnerDataListener;
use Oro\Bundle\OrganizationBundle\Ownership\EntityOwnershipAssociationsSetter;

class RecordOwnerDataListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityOwnershipAssociationsSetter|\PHPUnit\Framework\MockObject\MockObject */
    private $entityOwnershipAssociationsSetter;

    /** @var RecordOwnerDataListener */
    private $listener;

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
