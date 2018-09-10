<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUsersWithSameEmailInLowercase;

class UserRepositoryTest extends WebTestCase
{
    /** @var UserRepository */
    private $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository(User::class);
    }

    public function testFindUserByEmailSensitive()
    {
        $this->loadFixtures([LoadUserData::class]);

        /** @var User $user */
        $user = $this->getReference(LoadUserData::SIMPLE_USER);

        $this->assertEquals($user, $this->repository->findUserByEmail(strtoupper($user->getEmail()), true));
        $this->assertEquals($user, $this->repository->findUserByEmail(ucfirst($user->getEmail()), true));
        $this->assertEquals($user, $this->repository->findUserByEmail($user->getEmail(), true));
    }

    public function testFindUserByEmailInsensitive()
    {
        $this->loadFixtures([LoadUserData::class]);

        /** @var User $user */
        $user = $this->getReference(LoadUserData::SIMPLE_USER);

        $this->assertTrue(null === $this->repository->findUserByEmail(strtoupper($user->getEmail()), false));
        $this->assertTrue(null === $this->repository->findUserByEmail(ucfirst($user->getEmail()), false));
        $this->assertEquals($user, $this->repository->findUserByEmail($user->getEmail(), false));
    }

    public function testFindLowercaseDuplicatedEmails()
    {
        $this->loadFixtures([LoadUsersWithSameEmailInLowercase::class]);

        $this->assertEquals(
            [LoadUsersWithSameEmailInLowercase::EMAIL],
            $this->repository->findLowercaseDuplicatedEmails(10)
        );
    }
}
