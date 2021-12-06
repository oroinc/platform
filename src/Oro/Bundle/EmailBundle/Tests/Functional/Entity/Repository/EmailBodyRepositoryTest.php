<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailBodyRepository;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailBodyRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadEmailData::class]);
    }

    public function testDeleteOrphanBodies()
    {
        $em = self::getContainer()->get('doctrine')->getManagerForClass(Email::class);
        /** @var Email[] $emails */
        $emails = self::getContainer()->get('doctrine')->getRepository(Email::class)->findAll();
        foreach ($emails as $email) {
            $em->remove($email);
        }
        $em->flush();

        /** @var EmailBodyRepository $repo */
        $repo = self::getContainer()->get('doctrine')->getRepository(EmailBody::class);
        self::assertNotEmpty($repo->findAll());

        $repo->deleteOrphanBodies();
        self::assertEmpty($repo->findAll());
    }
}
