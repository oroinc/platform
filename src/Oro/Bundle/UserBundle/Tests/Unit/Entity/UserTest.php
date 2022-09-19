<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserApi;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class UserTest extends AbstractUserTest
{
    public function getUser(): User
    {
        return new User();
    }

    public function testEmail(): void
    {
        $user = $this->getUser();
        $mail = 'tony@mail.org';

        self::assertNull($user->getEmail());

        $user->setEmail($mail);

        self::assertEquals($mail, $user->getEmail());
    }

    public function testGroups(): void
    {
        $user = $this->getUser();
        $role = new Role('ROLE_FOO');
        $group = new Group('Users');

        $group->addRole($role);

        self::assertNotContains($role, $user->getUserRoles());

        $user->addGroup($group);

        self::assertContains($group, $user->getGroups());
        self::assertContains('Users', $user->getGroupNames());
        self::assertTrue($user->hasRole($role));
        self::assertTrue($user->hasGroup('Users'));

        $user->removeGroup($group);

        self::assertFalse($user->hasRole($role));
    }

    public function testEmails(): void
    {
        $user = $this->getUser();
        $email = new Email();

        self::assertNotContains($email, $user->getEmails());

        $user->addEmail($email);

        self::assertContains($email, $user->getEmails());

        $user->removeEmail($email);

        self::assertNotContains($email, $user->getEmails());
    }

    public function testNames(): void
    {
        $user = $this->getUser();
        $first = 'James';
        $last = 'Bond';

        $user->setFirstName($first);
        $user->setLastName($last);
    }

    public function testDates(): void
    {
        $user = $this->getUser();
        $now = new \DateTime('-1 year');

        $user->setBirthday($now);
        $user->setLastLogin($now);

        self::assertEquals($now, $user->getBirthday());
        self::assertEquals($now, $user->getLastLogin());
    }

    public function provider(): array
    {
        return [
            ['username', 'test'],
            ['email', 'test'],
            ['nameprefix', 'test'],
            ['firstname', 'test'],
            ['middlename', 'test'],
            ['lastname', 'test'],
            ['namesuffix', 'test'],
            ['birthday', new \DateTime()],
            ['password', 'test'],
            ['plainPassword', 'test'],
            ['confirmationToken', 'test'],
            ['passwordRequestedAt', new \DateTime()],
            ['passwordChangedAt', new \DateTime()],
            ['lastLogin', new \DateTime()],
            ['loginCount', 11],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['salt', md5('user')],
        ];
    }

    public function testBeforeSave(): void
    {
        $user = $this->getUser();
        $user->beforeSave();
        self::assertInstanceOf(\DateTime::class, $user->getCreatedAt());
        self::assertInstanceOf(\DateTime::class, $user->getUpdatedAt());
        self::assertEquals(0, $user->getLoginCount());
    }

    public function testPreUpdateUnChanged(): void
    {
        $changeSet = [
            'lastLogin' => null,
            'loginCount' => null,
        ];

        $user = $this->getUser();
        $updatedAt = new \DateTime('2015-01-01');
        $user->setUpdatedAt($updatedAt)
            ->setConfirmationToken('test_token')
            ->setPasswordRequestedAt(new \DateTime());

        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects(self::any())
            ->method('getEntityChangeSet')
            ->willReturn($changeSet);

        self::assertEquals($updatedAt, $user->getUpdatedAt());
        self::assertNotNull($user->getConfirmationToken());
        self::assertNotNull($user->getPasswordRequestedAt());
        $user->preUpdate($event);
        self::assertEquals($updatedAt, $user->getUpdatedAt());
        self::assertNotNull($user->getConfirmationToken());
        self::assertNotNull($user->getPasswordRequestedAt());
    }

    /**
     * @dataProvider preUpdateDataProvider
     */
    public function testPreUpdateChanged(array $changeSet): void
    {
        $user = $this->getUser();
        $updatedAt = new \DateTime('2015-01-01');
        $user->setUpdatedAt($updatedAt)
            ->setConfirmationToken('test_token')
            ->setPasswordRequestedAt(new \DateTime());

        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects(self::any())
            ->method('getEntityChangeSet')
            ->willReturn($changeSet);

        self::assertEquals($updatedAt, $user->getUpdatedAt());
        self::assertNotNull($user->getConfirmationToken());
        self::assertNotNull($user->getPasswordRequestedAt());
        $user->preUpdate($event);
        self::assertNotEquals($updatedAt, $user->getUpdatedAt());
        self::assertNull($user->getConfirmationToken());
        self::assertNull($user->getPasswordRequestedAt());
    }

    public function preUpdateDataProvider(): array
    {
        return [
            [
                'changeSet' => array_flip(['username']),
            ],
            [
                'changeSet' => array_flip(['email']),
            ],
            [
                'changeSet' => array_flip(['password']),
            ],
            [
                'changeSet' => array_flip(['username', 'email'])
            ],
            [
                'changeSet' => array_flip(['email', 'password'])
            ],
            [
                'changeSet' => array_flip(['username', 'password'])
            ],
            [
                'changeSet' => array_flip(['username', 'email', 'password'])
            ],
        ];
    }

    public function testBusinessUnit(): void
    {
        $user = $this->getUser();
        $businessUnit = new BusinessUnit();

        $user->setBusinessUnits(new ArrayCollection([$businessUnit]));

        self::assertContains($businessUnit, $user->getBusinessUnits());

        $user->removeBusinessUnit($businessUnit);

        self::assertNotContains($businessUnit, $user->getBusinessUnits());

        $user->addBusinessUnit($businessUnit);

        self::assertContains($businessUnit, $user->getBusinessUnits());
    }

    public function testOwners(): void
    {
        $entity = $this->getUser();
        $businessUnit = new BusinessUnit();

        self::assertEmpty($entity->getOwner());

        $entity->setOwner($businessUnit);

        self::assertEquals($businessUnit, $entity->getOwner());
    }

    public function testImapConfiguration(): void
    {
        $entity = $this->getUser();
        $imapConfiguration = $this->createMock(UserEmailOrigin::class);
        $imapConfiguration->expects(self::once())
            ->method('setActive')
            ->with(false);
        $imapConfiguration->expects(self::exactly(2))
            ->method('isActive')
            ->willReturn(true);
        $imapConfiguration->expects(self::once())
            ->method('getUser')
            ->willReturn($entity);

        self::assertCount(0, $entity->getEmailOrigins());
        self::assertNull($entity->getImapConfiguration());

        $entity->setImapConfiguration($imapConfiguration);
        self::assertEquals($imapConfiguration, $entity->getImapConfiguration());
        self::assertCount(1, $entity->getEmailOrigins());

        $entity->setImapConfiguration(null);
        self::assertNull($entity->getImapConfiguration());
        self::assertCount(0, $entity->getEmailOrigins());
    }

    public function testEmailOrigins(): void
    {
        $entity = $this->getUser();
        $origin1 = new InternalEmailOrigin();
        $origin2 = new InternalEmailOrigin();

        self::assertCount(0, $entity->getEmailOrigins());

        $entity->addEmailOrigin($origin1);
        $entity->addEmailOrigin($origin2);
        self::assertCount(2, $entity->getEmailOrigins());
        self::assertSame($origin1, $entity->getEmailOrigins()->first());
        self::assertSame($origin2, $entity->getEmailOrigins()->last());

        $entity->removeEmailOrigin($origin1);
        self::assertCount(1, $entity->getEmailOrigins());
        self::assertSame($origin2, $entity->getEmailOrigins()->first());
    }

    public function testGetApiKey(): void
    {
        $entity = $this->getUser();

        self::assertEmpty($entity->getApiKeys(), 'Should return some key, even if is not present');

        $organization1 = new Organization();
        $organization1->setName('test1');

        $organization2 = new Organization();
        $organization2->setName('test2');

        $apiKey1 = new UserApi();
        $apiKey1->setApiKey($apiKey1->generateKey());
        $apiKey1->setOrganization($organization1);

        $apiKey2 = new UserApi();
        $apiKey2->setApiKey($apiKey2->generateKey());
        $apiKey2->setOrganization($organization2);

        $entity->addApiKey($apiKey1);
        $entity->addApiKey($apiKey2);

        self::assertSame(
            $apiKey1->getApiKey(),
            $entity->getApiKeys()[0]->getApiKey(),
            'Should delegate call to userApi entity'
        );

        self::assertEquals(
            new ArrayCollection([$apiKey1, $apiKey2]),
            $entity->getApiKeys()
        );

        $entity->removeApiKey($apiKey2);
        self::assertEquals(
            new ArrayCollection([$apiKey1]),
            $entity->getApiKeys()
        );
    }

    public function testGetEmailFields(): void
    {
        $user = $this->getUser();
        self::assertIsArray($user->getEmailFields());
        self::assertEquals(['email'], $user->getEmailFields());
    }

    /**
     * @dataProvider setDataProviderAccountType
     */
    public function testGetAccountType(bool $skipOrigin, string $accessToken, string $accountType): void
    {
        $user = $this->getUser();
        $origin = $this->createMock(UserEmailOrigin::class);
        $origin->expects(self::any())
            ->method('getAccountType')
            ->willReturn($accountType);

        $user->addEmailOrigin($origin);

        if ($skipOrigin) {
            $user->removeEmailOrigin($origin);
            self::assertEmpty($user->getImapAccountType());
        } else {
            $origin->expects(self::once())
                ->method('isActive')
                ->willReturn(true);
            $origin->expects(self::once())
                ->method('getMailbox')
                ->willReturn(false);

            $origin->expects(self::any())
                ->method('getAccessToken')
                ->willReturn($accessToken);
            self::assertEquals($accountType, $user->getImapAccountType()->getAccountType());
        }
    }

    public function setDataProviderAccountType(): array
    {
        return [
            'empty origin' => [
                'skipOrigin' => true,
                'accessToken' => '',
                'accountType' => '',
            ],
            'expect Gmail account type' => [
                'skipOrigin' => false,
                'accessToken' => '12345',
                'accountType' => AccountTypeModel::ACCOUNT_TYPE_GMAIL
            ],
            'expect Microsoft account type' => [
                'skipOrigin' => false,
                'accessToken' => '12345',
                'accountType' => AccountTypeModel::ACCOUNT_TYPE_MICROSOFT
            ],
            'expect Other account type' => [
                'skipOrigin' => false,
                'accessToken' => '',
                'accountType' => AccountTypeModel::ACCOUNT_TYPE_OTHER
            ]
        ];
    }

    public function testOrganizations(): void
    {
        $user = $this->getUser();
        $disabledOrganization = new Organization();
        $disabledOrganization->setEnabled(false);
        $organization = new Organization();
        $organization->setEnabled(true);

        $user->setOrganizations(new ArrayCollection([$organization]));
        self::assertContains($organization, $user->getOrganizations());

        $user->removeOrganization($organization);
        self::assertNotContains($organization, $user->getOrganizations());

        $user->addOrganization($organization);
        self::assertContains($organization, $user->getOrganizations());

        $user->addOrganization($disabledOrganization);
        $result = $user->getOrganizations(true);
        self::assertCount(1, $result);
        self::assertSame($result->first(), $organization);
    }

    public function testSetEmailGetEmailLowercase(): void
    {
        $user = $this->getUser();
        $user->setEmail('John.Doe@example.org');

        self::assertEquals('john.doe@example.org', $user->getEmailLowercase());
    }

    public function testSetUsernameGetUsernameLowercase(): void
    {
        $user = $this->getUser();
        $user->setUsername('John');

        self::assertEquals('John', $user->getUsername());
        self::assertEquals('john', $user->getUsernameLowercase());
    }

    public function testUnserialize(): void
    {
        $serialized = [
            'password',
            'salt',
            'UserName',
            true,
            'confirmation_token',
            10,
        ];

        $user = $this->getUser();
        $user->__unserialize($serialized);

        self::assertEquals($serialized[0], $user->getPassword());
        self::assertEquals($serialized[1], $user->getSalt());
        self::assertEquals($serialized[2], $user->getUsername());
        self::assertEquals(mb_strtolower($serialized[2]), $user->getUsernameLowercase());
        self::assertEquals($serialized[3], $user->isEnabled());
        self::assertEquals($serialized[4], $user->getConfirmationToken());
        self::assertEquals($serialized[5], $user->getId());
    }
}
