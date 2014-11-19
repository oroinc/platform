<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Entity\Repository;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;

class ReminderRepositoryTest extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $em;

    protected function setUp()
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Bundle\ReminderBundle\Entity'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            array(
                'OroReminderBundle' => 'Oro\Bundle\ReminderBundle\Entity'
            )
        );
    }

    public function testGetTaskListByTimeIntervalQueryBuilder()
    {
        $entityClassName = 'Test\Entity';
        $entityIds       = [1, 2, 3];

        /** @var ReminderRepository $repo */
        $repo = $this->em->getRepository('OroReminderBundle:Reminder');
        $qb   = $repo->findRemindersByEntitiesQueryBuilder($entityClassName, $entityIds);

        $this->assertEquals(
            'SELECT reminder'
            . ' FROM Oro\Bundle\ReminderBundle\Entity\Reminder reminder'
            . ' WHERE reminder.relatedEntityClassName = :className AND reminder.relatedEntityId IN (:ids)',
            $qb->getDQL()
        );
        $this->assertEquals($entityClassName, $qb->getParameter('className')->getValue());
        $this->assertEquals($entityIds, $qb->getParameter('ids')->getValue());
    }
}
