<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Entity\Repository;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class ReminderRepositoryTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
    }

    public function testFindRemindersByEntitiesQueryBuilder()
    {
        $entityClassName = 'Test\Entity';
        $entityIds = [1, 2, 3];

        /** @var ReminderRepository $repo */
        $repo = $this->em->getRepository(Reminder::class);
        $qb = $repo->findRemindersByEntitiesQueryBuilder($entityClassName, $entityIds);

        $this->assertEquals(
            'SELECT reminder'
            . ' FROM ' . Reminder::class . ' reminder'
            . ' WHERE reminder.relatedEntityClassName = :className AND reminder.relatedEntityId IN (:ids)',
            $qb->getDQL()
        );
        $this->assertEquals($entityClassName, $qb->getParameter('className')->getValue());
        $this->assertEquals($entityIds, $qb->getParameter('ids')->getValue());
    }
}
