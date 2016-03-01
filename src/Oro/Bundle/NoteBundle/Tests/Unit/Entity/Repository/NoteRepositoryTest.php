<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Entity\Repository;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\NoteBundle\Entity\Repository\NoteRepository;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;

class NoteRepositoryTest extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $em;

    protected function setUp()
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Bundle\ActivityBundle\Tests\Unit\Fixtures\Entity'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'OroNoteBundle' => 'Oro\Bundle\NoteBundle\Entity'
            ]
        );
    }

    public function testGetAssociatedNotesQueryBuilder()
    {
        /** @var NoteRepository $repo */
        $repo = $this->em->getRepository('OroNoteBundle:Note');

        $qb = $repo->getAssociatedNotesQueryBuilder('Test\Entity', 123);

        $this->assertEquals(
            'SELECT partial note.{id, message, owner, createdAt, updatedBy, updatedAt}, c, u'
            . ' FROM Oro\Bundle\NoteBundle\Entity\Note note'
            . ' INNER JOIN Test\Entity e WITH note.entity_2929d33a = e'
            . ' LEFT JOIN note.owner c'
            . ' LEFT JOIN note.updatedBy u'
            . ' WHERE e.id IN(123)',
            $qb->getDQL()
        );
        $this->assertNull($qb->getFirstResult());
        $this->assertNull($qb->getMaxResults());
    }

    public function testGetAssociatedNotesQueryBuilderWithPaging()
    {
        /** @var NoteRepository $repo */
        $repo = $this->em->getRepository('OroNoteBundle:Note');

        $qb = $repo->getAssociatedNotesQueryBuilder('Test\Entity', 123, 10, 50);

        $this->assertEquals(
            'SELECT partial note.{id, message, owner, createdAt, updatedBy, updatedAt}, c, u'
            . ' FROM Oro\Bundle\NoteBundle\Entity\Note note'
            . ' INNER JOIN Test\Entity e WITH note.entity_2929d33a = e'
            . ' LEFT JOIN note.owner c'
            . ' LEFT JOIN note.updatedBy u'
            . ' WHERE e.id IN(123)',
            $qb->getDQL()
        );

        $this->assertEquals(450, $qb->getFirstResult());
        $this->assertEquals(50, $qb->getMaxResults());
    }
}
