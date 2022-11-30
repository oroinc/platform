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

    public function testFindMessageIdReturnsNullWhenNoEmailFound(): void
    {
        self::assertNull($this->getRepository()->findMessageIdByEmailId(PHP_INT_MAX));
    }

    public function testFindMessageId(): void
    {
        $email = $this->getReference('email_1');

        self::assertEquals($email->getMessageId(), $this->getRepository()->findMessageIdByEmailId($email->getId()));
    }

    public function testGetEmailUserIdsByEmailAddressQbByFromAddress(): void
    {
        $userEmailAddress = $this->getReference('simple_user')->getEmail();
        $emailUsers = $this->getRepository()
            ->getEmailUserIdsByEmailAddressQb($userEmailAddress)
            ->getQuery()
            ->getArrayResult();
        self::assertCount(10, $emailUsers);
    }

    public function testGetEmailUserIdsByEmailAddressQbByCcAddress(): void
    {
        $emailUsers = $this->getRepository()
            ->getEmailUserIdsByEmailAddressQb('cc1@example.com')
            ->getQuery()
            ->getArrayResult();
        self::assertCount(1, $emailUsers);
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
