<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Entity\Repository;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailFolderRepository;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class ImapEmailFolderRepositoryTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
    }

    public function testGetFoldersByOriginQueryBuilder()
    {
        $origin = new UserEmailOrigin();

        /** @var ImapEmailFolderRepository $repo */
        $repo = $this->em->getRepository(ImapEmailFolder::class);
        $query = $repo->getFoldersByOriginQueryBuilder($origin)->getQuery();

        $this->assertEquals(
            'SELECT imap_folder'
            . ' FROM Oro\Bundle\ImapBundle\Entity\ImapEmailFolder imap_folder'
            . ' INNER JOIN imap_folder.folder folder'
            . ' WHERE folder.origin = :origin AND folder.outdatedAt IS NULL',
            $query->getDQL()
        );

        $this->assertSame($origin, $query->getParameter('origin')->getValue());
    }

    public function testGetFoldersByOriginQueryBuilderWithOutdated()
    {
        $origin = new UserEmailOrigin();

        /** @var ImapEmailFolderRepository $repo */
        $repo = $this->em->getRepository(ImapEmailFolder::class);
        $query = $repo->getFoldersByOriginQueryBuilder($origin, true)->getQuery();

        $this->assertEquals(
            'SELECT imap_folder'
            . ' FROM Oro\Bundle\ImapBundle\Entity\ImapEmailFolder imap_folder'
            . ' INNER JOIN imap_folder.folder folder'
            . ' WHERE folder.origin = :origin',
            $query->getDQL()
        );

        $this->assertSame($origin, $query->getParameter('origin')->getValue());
    }

    public function testGetEmptyOutdatedFoldersByOriginQueryBuilder()
    {
        $origin = new UserEmailOrigin();

        /** @var ImapEmailFolderRepository $repo */
        $repo = $this->em->getRepository(ImapEmailFolder::class);
        $query = $repo->getEmptyOutdatedFoldersByOriginQueryBuilder($origin)->getQuery();

        $this->assertEquals(
            'SELECT imap_folder'
            . ' FROM Oro\Bundle\ImapBundle\Entity\ImapEmailFolder imap_folder'
            . ' INNER JOIN imap_folder.folder folder'
            . ' LEFT JOIN folder.emailUsers emailUsers'
            . ' WHERE (folder.outdatedAt IS NOT NULL AND emailUsers.id IS NULL) AND folder.origin = :origin',
            $query->getDQL()
        );

        $this->assertSame($origin, $query->getParameter('origin')->getValue());
    }
}
