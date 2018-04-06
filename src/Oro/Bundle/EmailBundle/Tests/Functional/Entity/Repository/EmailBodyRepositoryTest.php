<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailBodyRepository;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailBodyRepositoryTest extends WebTestCase
{
    /** @var DoctrineHelper */
    protected $doctrineHeler;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadEmailData::class]);
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

        /** @var EmailBodyRepository $repo */
        $repo = $this->doctrineHeler->getEntityRepositoryForClass(EmailBody::class);
        self::assertNotEmpty($repo->findAll());

        $repo->deleteOrphanBodies();
        self::assertEmpty($repo->findAll());
    }
}
