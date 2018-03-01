<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailThreadRepository;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailThreadedData;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailThreadRepositoryTest extends WebTestCase
{
    /** @var DoctrineHelper */
    protected $doctrineHeler;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadEmailThreadedData::class]);
        $this->doctrineHeler = $this->getContainer()->get('oro_entity.doctrine_helper');
    }

    public function testDeleteOrphanBodies()
    {
        $em = $this->doctrineHeler->getEntityManagerForClass(Email::class);
        /** @var Email[] $emails */
        $emails = $this->doctrineHeler->getEntityRepositoryForClass(Email::class)->findAll();
        foreach ($emails as $email) {
            $em->remove($email);
        }
        $em->flush();

        /** @var EmailThreadRepository $repo */
        $repo = $this->doctrineHeler->getEntityRepositoryForClass(EmailThread::class);
        self::assertNotEmpty($repo->findAll());

        $repo->deleteOrphanThreads();
        self::assertEmpty($repo->findAll());
    }
}
