<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailThreadRepository;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailThreadedData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailThreadRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadEmailThreadedData::class]);
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

        /** @var EmailThreadRepository $repo */
        $repo = self::getContainer()->get('doctrine')->getRepository(EmailThread::class);
        self::assertNotEmpty($repo->findAll());

        $repo->deleteOrphanThreads();
        self::assertEmpty($repo->findAll());
    }
}
