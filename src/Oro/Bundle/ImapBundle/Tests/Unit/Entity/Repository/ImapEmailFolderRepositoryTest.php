<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Entity\Repository;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailFolderRepository;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;

class ImapEmailFolderRepositoryTest extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $em;

    protected function setUp()
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            [
                'Oro\Bundle\ImapBundle\Entity',
                'Oro\Bundle\EmailBundle\Entity',
            ]
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'OroImapBundle' => 'Oro\Bundle\ImapBundle\Entity'
            ]
        );
    }

    public function testGetFoldersByOriginQueryBuilder()
    {
        $origin = new UserEmailOrigin();

        /** @var ImapEmailFolderRepository $repo */
        $repo = $this->em->getRepository('OroImapBundle:ImapEmailFolder');

        $qb    = $repo->getFoldersByOriginQueryBuilder($origin);
        $query = $qb->getQuery();

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
        $repo = $this->em->getRepository('OroImapBundle:ImapEmailFolder');

        $qb    = $repo->getFoldersByOriginQueryBuilder($origin, true);
        $query = $qb->getQuery();

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
        $repo = $this->em->getRepository('OroImapBundle:ImapEmailFolder');

        $qb    = $repo->getEmptyOutdatedFoldersByOriginQueryBuilder($origin);
        $query = $qb->getQuery();

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
