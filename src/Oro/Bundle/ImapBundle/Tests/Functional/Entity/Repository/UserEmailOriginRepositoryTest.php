<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\ImapBundle\Entity\Repository\UserEmailOriginRepository;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures\LoadEmailUserData;
use Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures\LoadImapEmailData;
use Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures\LoadTypedUserEmailOriginData;
use Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures\LoadUserEmailOriginData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class UserEmailOriginRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    private function getRepository(): UserEmailOriginRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(UserEmailOrigin::class);
    }

    private function getEntitiesCount(string $entityClass): int
    {
        $repository = $this->getContainer()->get('doctrine')->getRepository($entityClass);

        return count($repository->findAll());
    }

    public function testDeleteRelatedEmails()
    {
        $this->loadFixtures([LoadEmailUserData::class]);

        /** @var UserEmailOrigin $origin */
        $origin = $this->getReference(LoadUserEmailOriginData::USER_EMAIL_ORIGIN_1);

        $this->assertEquals(10, $this->getEntitiesCount(Email::class));

        $this->getRepository()->deleteRelatedEmails($origin);

        $this->assertEquals(7, $this->getEntitiesCount(Email::class));
    }

    public function testDeleteRelatedEmailsSyncDisabled()
    {
        $this->loadFixtures([LoadImapEmailData::class]);

        /** @var UserEmailOrigin $origin */
        $origin = $this->getReference(LoadUserEmailOriginData::USER_EMAIL_ORIGIN_3);

        $this->assertEquals(10, $this->getEntitiesCount(Email::class));

        $this->getRepository()->deleteRelatedEmails($origin, false);

        $this->assertEquals(8, $this->getEntitiesCount(Email::class));
    }

    public function testDeleteRelatedEmailsSyncEnabled()
    {
        $this->loadFixtures([LoadImapEmailData::class]);

        /** @var UserEmailOrigin $origin */
        $origin = $this->getReference(LoadUserEmailOriginData::USER_EMAIL_ORIGIN_1);

        $this->assertEquals(10, $this->getEntitiesCount(Email::class));

        $this->getRepository()->deleteRelatedEmails($origin, true);

        $this->assertEquals(7, $this->getEntitiesCount(Email::class));
    }

    /**
     * @dataProvider getOriginsData
     */
    public function testGetOrigins(callable $qbCallback, callable $getTokenCallback, $expectedCount): void
    {
        $this->loadFixtures([LoadTypedUserEmailOriginData::class]);

        $tokens = $qbCallback($this->getRepository())->getQuery()->execute();
        $this->assertCount($expectedCount, $tokens);
        foreach ($tokens as $token) {
            $this->assertEquals(8192, strlen($getTokenCallback($token)));
        }
    }

    public function getOriginsData(): array
    {
        return [
            [
                fn (UserEmailOriginRepository $repo) => $repo->getAllOriginsWithAccessTokens('gmail'),
                fn (UserEmailOrigin $emailOrigin) => $emailOrigin->getAccessToken(),
                1
            ],
            [
                fn (UserEmailOriginRepository $repo) => $repo->getAllOriginsWithAccessTokens('microsoft'),
                fn (UserEmailOrigin $emailOrigin) => $emailOrigin->getAccessToken(),
                1
            ],
            [
                fn (UserEmailOriginRepository $repo) => $repo->getAllOriginsWithAccessTokens(),
                fn (UserEmailOrigin $emailOrigin) => $emailOrigin->getAccessToken(),
                2
            ],
            [
                fn (UserEmailOriginRepository $repo) => $repo->getAllOriginsWithRefreshTokens('gmail'),
                fn (UserEmailOrigin $emailOrigin) => $emailOrigin->getRefreshToken(),
                1
            ],
            [
                fn (UserEmailOriginRepository $repo) => $repo->getAllOriginsWithRefreshTokens('microsoft'),
                fn (UserEmailOrigin $emailOrigin) => $emailOrigin->getRefreshToken(),
                1
            ],
            [
                fn (UserEmailOriginRepository $repo) => $repo->getAllOriginsWithRefreshTokens(),
                fn (UserEmailOrigin $emailOrigin) => $emailOrigin->getRefreshToken(),
                2
            ]
        ];
    }

    public function testGetEmailIdsFromDisabledFoldersIterator()
    {
        $this->loadFixtures([LoadImapEmailData::class]);

        $iterator = $this->getRepository()
            ->getEmailIdsFromDisabledFoldersIterator(
                $this->getReference(LoadUserEmailOriginData::USER_EMAIL_ORIGIN_3)
            );

        self::assertEquals(2, $iterator->count());
    }
}
