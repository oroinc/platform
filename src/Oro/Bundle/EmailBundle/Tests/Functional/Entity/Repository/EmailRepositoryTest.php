<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailRepositoryTest extends WebTestCase
{
    private EmailRepository $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadEmailData::class]);

        $this->repository = self::getContainer()
            ->get('doctrine')
            ->getRepository(Email::class);
    }

    public function testFindMessageIdReturnsNullWhenNoEmailFound(): void
    {
        self::assertNull($this->repository->findMessageIdByEmailId(PHP_INT_MAX));
    }

    public function testFindMessageId(): void
    {
        $email = $this->getReference('email_1');

        self::assertEquals($email->getMessageId(), $this->repository->findMessageIdByEmailId($email->getId()));
    }
}
