<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Entity\Provider;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\User;

class EmailOwnerProviderTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            '@OroUserBundle/Tests/Functional/Entity/Provider/DataFixtures/email_owner_provider.yml'
        ]);
    }

    private function getProvider(): EmailOwnerProviderInterface
    {
        return self::getContainer()->get('oro_user.email.owner.provider');
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(User::class);
    }

    private function assertCaseInsensitiveSearchSupported(): void
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();
        if ($conn->getDatabasePlatform() instanceof MySqlPlatform) {
            $supported = (bool)$conn->fetchAll(
                'SELECT 1 FROM information_schema.columns WHERE '
                . 'TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND COLLATION_NAME LIKE ? LIMIT 1;',
                [$conn->getDatabase(), $em->getClassMetadata(Email::class)->getTableName(), 'email', '%_ci']
            );
            if (!$supported) {
                self::markTestSkipped('Case insensitive email search is not supported.');
            }
        }
    }

    public function caseInsensitiveSearchDataProvider(): array
    {
        return [[true], [false]];
    }

    public function testGetEmailOwnerClass(): void
    {
        self::assertEquals(User::class, $this->getProvider()->getEmailOwnerClass());
    }

    /**
     * @dataProvider caseInsensitiveSearchDataProvider
     */
    public function testFindEmailOwner(bool $caseInsensitiveSearch): void
    {
        $email = 'jane.smith@example.com';
        if ($caseInsensitiveSearch) {
            $this->assertCaseInsensitiveSearchSupported();
            $email = strtoupper($email);
        }

        /** @var User $owner */
        $owner = $this->getProvider()->findEmailOwner($this->getEntityManager(), $email);
        self::assertInstanceOf(User::class, $owner);
        self::assertSame($this->getReference('user4')->getId(), $owner->getId());
    }

    /**
     * @dataProvider caseInsensitiveSearchDataProvider
     */
    public function testFindEmailOwnerByNotPrimaryEmail(bool $caseInsensitiveSearch): void
    {
        $email = 'jane.smith@another.com';
        if ($caseInsensitiveSearch) {
            $this->assertCaseInsensitiveSearchSupported();
            $email = strtoupper($email);
        }

        /** @var User $owner */
        $owner = $this->getProvider()->findEmailOwner($this->getEntityManager(), $email);
        self::assertInstanceOf(User::class, $owner);
        self::assertSame($this->getReference('user4')->getId(), $owner->getId());
    }

    public function testFindEmailOwnerWhenItDoesNotExist(): void
    {
        $owner = $this->getProvider()->findEmailOwner($this->getEntityManager(), 'another@example.com');
        self::assertNull($owner);
    }

    public function testFindEmailOwnerWhenEmailDuplicated(): void
    {
        $owner = $this->getProvider()->findEmailOwner($this->getEntityManager(), 'test@example.com');
        self::assertInstanceOf(User::class, $owner);
        self::assertSame($this->getReference('user2')->getId(), $owner->getId());
    }
    /**
     * @dataProvider caseInsensitiveSearchDataProvider
     */
    public function testGetOrganizations(bool $caseInsensitiveSearch): void
    {
        $email = 'jane.smith@example.com';
        if ($caseInsensitiveSearch) {
            $this->assertCaseInsensitiveSearchSupported();
            $email = strtoupper($email);
        }

        $organizations = $this->getProvider()->getOrganizations($this->getEntityManager(), $email);
        self::assertSame(
            [$this->getReference('organization')->getId()],
            $organizations
        );
    }

    /**
     * @dataProvider caseInsensitiveSearchDataProvider
     */
    public function testGetOrganizationsForSeveralOrganizations(bool $caseInsensitiveSearch): void
    {
        $email = 'john.smith@example.com';
        if ($caseInsensitiveSearch) {
            $this->assertCaseInsensitiveSearchSupported();
            $email = strtoupper($email);
        }

        $organizations = $this->getProvider()->getOrganizations($this->getEntityManager(), $email);
        sort($organizations);
        self::assertSame(
            [
                $this->getReference('organization')->getId(),
                $this->getReference('organization1')->getId(),
                $this->getReference('organization2')->getId()
            ],
            $organizations
        );
    }

    public function testGetEmails(): void
    {
        $emails = $this->getProvider()->getEmails(
            $this->getEntityManager(),
            $this->getReference('organization')->getId()
        );
        self::assertSame(
            [
                'admin@example.com',
                'john.smith@example.com',
                'nancy.sallee@example.com',
                'marlene.bradley@example.com',
                'jane.smith@example.com',
                'test@example.com',
                'test@example.com',
                'test@example.com',
                'jane.smith@another.com'
            ],
            iterator_to_array($emails)
        );
    }
}
