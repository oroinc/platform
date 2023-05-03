<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Entity\Repository;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailRepository;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class ImapEmailRepositoryTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
    }

    public function testGetEmailsByUidsQueryBuilder()
    {
        $folder = new EmailFolder();
        $uids = [1, 2];

        /** @var ImapEmailRepository $repo */
        $repo = $this->em->getRepository(ImapEmail::class);
        $query = $repo->getEmailsByUidsQueryBuilder($folder, $uids)->getQuery();

        $this->assertEquals(
            'SELECT imap_email '
            . 'FROM Oro\Bundle\ImapBundle\Entity\ImapEmail imap_email '
            . 'INNER JOIN imap_email.imapFolder imapFolder '
            . 'INNER JOIN imapFolder.folder folder '
            . 'WHERE folder = :folder AND imap_email.uid IN (:uids)',
            $query->getDQL()
        );

        $this->assertSame($folder, $query->getParameter('folder')->getValue());
        $this->assertEquals($uids, $query->getParameter('uids')->getValue());
    }

    public function testGetEmailsByMessageIdsQueryBuilder()
    {
        $origin = new UserEmailOrigin();
        $messageIds = ['msg1', 'msg2'];

        /** @var ImapEmailRepository $repo */
        $repo = $this->em->getRepository(ImapEmail::class);
        $query = $repo->getEmailsByMessageIdsQueryBuilder($origin, $messageIds)->getQuery();

        $this->assertEquals(
            'SELECT imap_email '
            . 'FROM Oro\Bundle\ImapBundle\Entity\ImapEmail imap_email '
            . 'INNER JOIN imap_email.imapFolder imap_folder '
            . 'INNER JOIN imap_email.email email '
            . 'INNER JOIN email.emailUsers email_users '
            . 'INNER JOIN email_users.folders folders '
            . 'WHERE folders.origin = :origin AND email.messageId IN (:messageIds)',
            $query->getDQL()
        );

        $this->assertSame($origin, $query->getParameter('origin')->getValue());
        $this->assertEquals($messageIds, $query->getParameter('messageIds')->getValue());
    }
}
