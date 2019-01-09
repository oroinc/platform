<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUsersWithSameEmailInLowercase;

/**
 * @dbIsolationPerTest
 */
class UserRepositoryTest extends WebTestCase
{
    /** @var UserRepository */
    private $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->repository = self::getContainer()->get('doctrine')
            ->getManagerForClass(User::class)
            ->getRepository(User::class);
    }

    public function testFindUserByEmailSensitive(): void
    {
        $this->loadFixtures([LoadUserData::class]);

        /** @var User $user */
        $user = $this->getReference(LoadUserData::SIMPLE_USER);

        $this->assertEquals($user, $this->repository->findUserByEmail(strtoupper($user->getEmail()), true));
        $this->assertEquals($user, $this->repository->findUserByEmail(ucfirst($user->getEmail()), true));
        $this->assertEquals($user, $this->repository->findUserByEmail($user->getEmail(), true));
    }

    public function testFindUserByEmailInsensitive(): void
    {
        $this->loadFixtures([LoadUserData::class]);

        /** @var User $user */
        $user = $this->getReference(LoadUserData::SIMPLE_USER);

        $this->assertTrue(null === $this->repository->findUserByEmail(strtoupper($user->getEmail()), false));
        $this->assertTrue(null === $this->repository->findUserByEmail(ucfirst($user->getEmail()), false));
        $this->assertEquals($user, $this->repository->findUserByEmail($user->getEmail(), false));
    }

    public function testFindLowercaseDuplicatedEmails(): void
    {
        $this->loadFixtures([LoadUsersWithSameEmailInLowercase::class]);

        $this->assertEquals(
            [LoadUsersWithSameEmailInLowercase::EMAIL],
            $this->repository->findLowercaseDuplicatedEmails(10)
        );
    }

    public function testFindEnabledUserEmails(): void
    {
        $this->loadFixtures([LoadUserData::class]);

        $result = $this->repository->findEnabledUserEmails();
        self::assertCount(4, $result);

        /**
         * @var User $adminUser
         * @var User $simpleUser
         * @var User $simpleUser2
         * @var User $userWithConfirmationToken
         */
        $adminUser = $this->repository->findOneBy(['email' => LoadAdminUserData::DEFAULT_ADMIN_EMAIL]);
        $simpleUser = $this->getReference(LoadUserData::SIMPLE_USER);
        $simpleUser2 = $this->getReference(LoadUserData::SIMPLE_USER_2);
        $userWithConfirmationToken = $this->getReference(LoadUserData::USER_WITH_CONFIRMATION_TOKEN);
        self::assertEquals([
            ['id' => $adminUser->getId(), 'email' => $adminUser->getEmail()],
            ['id' => $simpleUser->getId(), 'email' => $simpleUser->getEmail()],
            ['id' => $simpleUser2->getId(), 'email' => $simpleUser2->getEmail()],
            ['id' => $userWithConfirmationToken->getId(), 'email' => $userWithConfirmationToken->getEmail()],
        ], $result);
    }

    public function testFindIdsByOrganizations(): void
    {
        $this->loadFixtures([LoadUserData::class]);

        $organization = self::getContainer()->get('doctrine')
            ->getManagerForClass(Organization::class)
            ->getRepository(Organization::class)
            ->getFirst();

        $this->assertFalse(null === $organization);

        $user1 = $this->repository->findOneBy(['username' => LoadAdminUserData::DEFAULT_ADMIN_USERNAME]);
        $user2 = $this->getReference(LoadUserData::SIMPLE_USER);
        $user3 = $this->getReference(LoadUserData::SIMPLE_USER_2);
        $user4 = $this->getReference(LoadUserData::USER_WITH_CONFIRMATION_TOKEN);

        $expected = [$user1->getId(), $user2->getId(), $user3->getId(), $user4->getId()];
        sort($expected);

        $actual = $this->repository->findIdsByOrganizations([$organization]);
        sort($actual);

        $this->assertEquals($expected, $actual);
    }
}
