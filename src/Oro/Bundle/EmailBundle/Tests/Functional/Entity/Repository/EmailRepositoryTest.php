<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadEmailData::class]);
    }

    private function getRepository(): EmailRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(Email::class);
    }

    public function testGetEmailsByEmailAddressByFromAddress(): void
    {
        $userEmailAddress = $this->getReference('simple_user')->getEmail();
        $emails = $this->getRepository()->getEmailsByEmailAddress($userEmailAddress);
        self::assertCount(10, $emails);
    }

    public function testGetEmailsByEmailAddressByCcAddress(): void
    {
        $emails = $this->getRepository()->getEmailsByEmailAddress('cc1@example.com');
        self::assertCount(1, $emails);
    }

    public function testIsEmailPublicForPublicEmail(): void
    {
        /** @var EmailUser $emailUser */
        $emailUser = $this->getReference('emailUser_10');
        $emailUser->setIsEmailPrivate(false);
        self::getContainer()->get('doctrine')->getManagerForClass(EmailUser::class)->flush();

        $email = $this->getReference('email_10');

        self::assertTrue($this->getRepository()->isEmailPublic($email->getId()));
    }

    public function testIsEmailPublicForPrivateEmail(): void
    {
        $email = $this->getReference('email_2');

        self::assertFalse($this->getRepository()->isEmailPublic($email->getId()));
    }
}
