<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailUserRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadEmailData::class]);
    }

    public function testGetIdsFromOrigin()
    {
        $owner = $this->getReference('simple_user');
        $origin = self::getContainer()->get('oro_email.tools.email_origin_helper')
            ->getEmailOrigin($owner->getEmail());

        /** @var EmailUserRepository $repo */
        $repo = self::getContainer()->get('doctrine')->getRepository(EmailUser::class);

        $result = $repo->getIdsFromOrigin($origin);
        $this->assertCount(10, $result);

        for ($i = 1; $i <= 10; $i++) {
            $email = $this->getReference('emailUser_' . $i);
            $this->assertNotNull($email);
            $this->assertContains($email->getId(), $result);
        }
    }
}
