<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUsersWithSameEmailInLowercase;

/**
 * @dbIsolationPerTest
 */
class UserRepositoryTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    private function getRepository(): UserRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(User::class);
    }

    public function testFindUserByEmailSensitive(): void
    {
        $this->loadFixtures([LoadUserData::class]);

        /** @var User $user */
        $user = $this->getReference(LoadUserData::SIMPLE_USER);

        self::assertEquals($user, $this->getRepository()->findUserByEmail(strtoupper($user->getEmail()), true));
        self::assertEquals($user, $this->getRepository()->findUserByEmail(ucfirst($user->getEmail()), true));
        self::assertEquals($user, $this->getRepository()->findUserByEmail($user->getEmail(), true));
    }

    public function testFindUserByEmailInsensitive(): void
    {
        $this->loadFixtures([LoadUserData::class]);

        /** @var User $user */
        $user = $this->getReference(LoadUserData::SIMPLE_USER);

        self::assertTrue(null === $this->getRepository()->findUserByEmail(strtoupper($user->getEmail()), false));
        self::assertTrue(null === $this->getRepository()->findUserByEmail(ucfirst($user->getEmail()), false));
        self::assertEquals($user, $this->getRepository()->findUserByEmail($user->getEmail(), false));
    }

    public function testFindLowercaseDuplicatedEmails(): void
    {
        $this->loadFixtures([LoadUsersWithSameEmailInLowercase::class]);

        self::assertEquals(
            [LoadUsersWithSameEmailInLowercase::EMAIL],
            $this->getRepository()->findLowercaseDuplicatedEmails(10)
        );
    }

    public function testFindEnabledUserEmails(): void
    {
        $this->loadFixtures([LoadUserData::class, LoadUser::class]);

        $result = $this->getRepository()->findEnabledUserEmails();
        self::assertCount(4, $result);

        /** @var User $adminUser */
        $adminUser = $this->getReference(LoadUser::USER);
        /** @var User $simpleUser */
        $simpleUser = $this->getReference(LoadUserData::SIMPLE_USER);
        /** @var User $simpleUser2 */
        $simpleUser2 = $this->getReference(LoadUserData::SIMPLE_USER_2);
        /** @var User $userWithConfirmationToken */
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
        $this->loadFixtures([LoadUserData::class, LoadUser::class, LoadOrganization::class]);

        $organization = $this->getReference(LoadOrganization::ORGANIZATION);

        $user1 = $this->getReference(LoadUser::USER);
        $user2 = $this->getReference(LoadUserData::SIMPLE_USER);
        $user3 = $this->getReference(LoadUserData::SIMPLE_USER_2);
        $user4 = $this->getReference(LoadUserData::USER_WITH_CONFIRMATION_TOKEN);

        $expected = [$user1->getId(), $user2->getId(), $user3->getId(), $user4->getId()];
        sort($expected);

        $actual = $this->getRepository()->findIdsByOrganizations([$organization]);
        sort($actual);

        self::assertEquals($expected, $actual);
    }

    public function testGetUsersCount(): void
    {
        $this->loadFixtures([LoadUserData::class]);

        /** @var User $user */
        $user = $this->getReference(LoadUserData::SIMPLE_USER);
        $user->setEnabled(false);
        self::getContainer()->get('oro_user.manager')->updateUser($user);

        self::assertEquals(4, $this->getRepository()->getUsersCount());

        self::assertEquals(3, $this->getRepository()->getUsersCount(true));
    }

    public function testGetUsersCountQueryBuilder(): void
    {
        $this->loadFixtures([LoadUserData::class]);

        /** @var User $user */
        $user = $this->getReference(LoadUserData::SIMPLE_USER);
        $user->setEnabled(false);
        self::getContainer()->get('oro_user.manager')->updateUser($user);

        $result = $this->getRepository()->getUsersCountQueryBuilder()
            ->getQuery()
            ->getSingleScalarResult();

        self::assertEquals(4, $result);

        $result = $this->getRepository()->getUsersCountQueryBuilder(true)
            ->getQuery()
            ->getSingleScalarResult();

        self::assertEquals(3, $result);
    }
}
