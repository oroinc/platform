<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EmailBundle\Entity\Email;
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
}
