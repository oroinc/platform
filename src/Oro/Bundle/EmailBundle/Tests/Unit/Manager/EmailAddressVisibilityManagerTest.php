<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL100Platform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\Topic\RecalculateEmailVisibilityTopic;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAddressVisibility;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\EmailBundle\Entity\Provider\PublicEmailOwnerProvider;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\EmailAddress;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class EmailAddressVisibilityManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailOwnerProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $emailOwnerProvider;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $producer;

    /** @var PublicEmailOwnerProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $publicEmailOwnerProvider;

    /** @var EmailAddressVisibilityManager */
    private $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->connection = $this->createMock(Connection::class);
        $this->emailOwnerProvider = $this->createMock(EmailOwnerProvider::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->publicEmailOwnerProvider = $this->createMock(PublicEmailOwnerProvider::class);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::any())
            ->method('getTableName')
            ->willReturn('oro_email_address_visibility');

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(EmailAddressVisibility::class)
            ->willReturn($this->em);
        $this->em->expects(self::any())
            ->method('getConnection')
            ->willReturn($this->connection);
        $this->em->expects(self::any())
            ->method('getClassMetadata')
            ->with(EmailAddressVisibility::class)
            ->willReturn($metadata);

        $this->manager = new EmailAddressVisibilityManager(
            $this->emailOwnerProvider,
            $doctrine,
            $this->producer,
            $this->publicEmailOwnerProvider
        );
    }

    private function getOrganization(int $id): Organization
    {
        $organization = new Organization();
        $organization->setId($id);

        return $organization;
    }

    private function getEmailAddress(string $email): EmailAddress
    {
        $address = new EmailAddress();
        $address->setEmail($email);

        return $address;
    }

    private function getEmailRecipient(string $email, string $type): EmailRecipient
    {
        $recipient = new EmailRecipient();
        $recipient->setEmailAddress($this->getEmailAddress($email));
        $recipient->setType($type);

        return $recipient;
    }

    public function testProcessEmailUserVisibilityWhenEmailIsPublic(): void
    {
        $organizationId = 23;
        $organization = $this->getOrganization($organizationId);

        $email = new Email();
        $email->setFromEmailAddress($this->getEmailAddress('FromÄ@test.Com'));
        $email->addRecipient($this->getEmailRecipient('ToÄ@test.cOm', EmailRecipient::TO));
        $email->addRecipient($this->getEmailRecipient('toÄ@test.com', EmailRecipient::CC));
        $email->addRecipient($this->getEmailRecipient('CcÄ@test.cOm', EmailRecipient::CC));
        $email->addRecipient($this->getEmailRecipient('bcc@test.com', EmailRecipient::BCC));

        $emailUser = new EmailUser();
        $emailUser->setEmail($email);
        $emailUser->setOrganization($organization);

        $this->connection->expects(self::once())
            ->method('fetchOne')
            ->with(
                'SELECT 1 FROM oro_email_address_visibility WHERE'
                . ' is_visible = ? AND organization_id = ? AND email IN (?) LIMIT 1',
                [true, $organizationId, ['fromä@test.com', 'toä@test.com', 'ccä@test.com']],
                [Types::BOOLEAN, Types::INTEGER, Connection::PARAM_STR_ARRAY]
            )
            ->willReturn(1);

        $this->manager->processEmailUserVisibility($emailUser);

        self::assertFalse($emailUser->isEmailPrivate());
    }

    public function testProcessEmailUserVisibilityWhenEmailIsPrivate(): void
    {
        $organizationId = 24;
        $organization = $this->getOrganization($organizationId);

        $email = new Email();
        $email->setFromEmailAddress($this->getEmailAddress('FromÄ@test.Com'));

        $emailUser = new EmailUser();
        $emailUser->setEmail($email);
        $emailUser->setOrganization($organization);

        $this->connection->expects(self::once())
            ->method('fetchOne')
            ->with(
                'SELECT 1 FROM oro_email_address_visibility WHERE'
                . ' is_visible = ? AND organization_id = ? AND email IN (?) LIMIT 1',
                [true, $organizationId, ['fromä@test.com']],
                [Types::BOOLEAN, Types::INTEGER, Connection::PARAM_STR_ARRAY]
            )
            ->willReturn(false);

        $this->manager->processEmailUserVisibility($emailUser);

        self::assertTrue($emailUser->isEmailPrivate());
    }

    public function testUpdateEmailAddressVisibilityForMySqlDatabase(): void
    {
        $emailAddress = 'TestÄ@test.cOm';
        $visible = true;
        $organizationId = 33;

        $this->connection->expects(self::once())
            ->method('executeStatement')
            ->with(
                'INSERT INTO oro_email_address_visibility (email, organization_id, is_visible) VALUES (?, ?, ?) '
                . 'ON DUPLICATE KEY UPDATE is_visible = ?',
                ['testä@test.com', $organizationId, $visible, $visible],
                [Types::STRING, Types::INTEGER, Types::BOOLEAN, Types::BOOLEAN]
            );
        $this->connection->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->manager->updateEmailAddressVisibility($emailAddress, $organizationId, $visible);
    }

    public function testUpdateEmailAddressVisibilityForPostgreSqlDatabase(): void
    {
        $emailAddress = 'TestÄ@test.cOm';
        $visible = false;
        $organizationId = 34;

        $this->connection->expects(self::once())
            ->method('executeStatement')
            ->with(
                'INSERT INTO oro_email_address_visibility (email, organization_id, is_visible) VALUES (?, ?, ?) '
                . 'ON CONFLICT (email, organization_id) DO UPDATE SET is_visible = ?',
                ['testä@test.com', $organizationId, $visible, $visible],
                [Types::STRING, Types::INTEGER, Types::BOOLEAN, Types::BOOLEAN]
            );
        $this->connection->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn(new PostgreSQL100Platform());

        $this->manager->updateEmailAddressVisibility($emailAddress, $organizationId, $visible);
    }

    public function testUpdateEmailAddressVisibilities(): void
    {
        $organizationId = 33;

        $this->emailOwnerProvider->expects(self::once())
            ->method('getEmails')
            ->with(self::identicalTo($this->em), $organizationId)
            ->willReturn(
                [
                    ['addr1@example.com', 'Test\Entity\PublicOwner1'],
                    ['addr2@example.com', 'Test\Entity\PrivateOwner1']
                ]
            );
        $this->publicEmailOwnerProvider->expects(self::exactly(2))
            ->method('isPublicEmailOwner')
            ->willReturnMap([
                ['Test\Entity\PublicOwner1', true],
                ['Test\Entity\PrivateOwner1', false]
            ]);
        $this->connection->expects(self::exactly(2))
            ->method('executeStatement')
            ->withConsecutive(
                [
                    'INSERT INTO oro_email_address_visibility (email, organization_id, is_visible) VALUES (?, ?, ?) '
                    . 'ON DUPLICATE KEY UPDATE is_visible = ?',
                    ['addr1@example.com', $organizationId, true, true],
                    [Types::STRING, Types::INTEGER, Types::BOOLEAN, Types::BOOLEAN]
                ],
                [
                    'INSERT INTO oro_email_address_visibility (email, organization_id, is_visible) VALUES (?, ?, ?) '
                    . 'ON DUPLICATE KEY UPDATE is_visible = ?',
                    ['addr2@example.com', $organizationId, false, false],
                    [Types::STRING, Types::INTEGER, Types::BOOLEAN, Types::BOOLEAN]
                ]
            );
        $this->connection->expects(self::exactly(2))
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->manager->updateEmailAddressVisibilities($organizationId);
    }

    public function testUpdateEmailAddressVisibilitiesWhenNoEmails(): void
    {
        $organizationId = 33;

        $this->emailOwnerProvider->expects(self::once())
            ->method('getEmails')
            ->with(self::identicalTo($this->em), $organizationId)
            ->willReturn([]);
        $this->publicEmailOwnerProvider->expects(self::never())
            ->method('isPublicEmailOwner');
        $this->connection->expects(self::never())
            ->method('executeStatement');
        $this->connection->expects(self::never())
            ->method('getDatabasePlatform');

        $this->manager->updateEmailAddressVisibilities($organizationId);
    }

    public function testCollectEmailAddressesWithPublicOwner(): void
    {
        $emailAddress = 'TestÄ@test.cOm';

        $this->emailOwnerProvider->expects(self::once())
            ->method('getOrganizations')
            ->with($this->em, $emailAddress)
            ->willReturn([\stdClass::class => [2]]);
        $this->publicEmailOwnerProvider->expects(self::once())
            ->method('isPublicEmailOwner')
            ->with(\stdClass::class)
            ->willReturn(true);
        $this->connection->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());
        $this->connection->expects(self::exactly(2))
            ->method('executeStatement')
            ->withConsecutive(
                [
                    'DELETE FROM oro_email_address_visibility WHERE email = ? AND organization_id NOT IN (?)',
                    ['testä@test.com', [2]],
                    [Types::STRING, Connection::PARAM_INT_ARRAY]
                ],
                [
                    'INSERT INTO oro_email_address_visibility (email, organization_id, is_visible) VALUES (?, ?, ?) '
                    . 'ON DUPLICATE KEY UPDATE is_visible = ?',
                    ['testä@test.com', 2, true, true],
                    [Types::STRING, Types::INTEGER, Types::BOOLEAN, Types::BOOLEAN]
                ]
            );
        $this->producer->expects(self::once())
            ->method('send')
            ->with(RecalculateEmailVisibilityTopic::getName(), ['email' => $emailAddress]);

        $this->manager->collectEmailAddresses([$emailAddress]);
    }

    public function testCollectEmailAddressesWithPublicAndPrivateOwners(): void
    {
        $emailAddress = 'TestÄ@test.cOm';

        $this->emailOwnerProvider->expects(self::once())
            ->method('getOrganizations')
            ->with($this->em, $emailAddress)
            ->willReturn(['PublicOwnerClass' => [4], 'PrivateOwnerClass' => [4]]);
        $this->publicEmailOwnerProvider->expects(self::exactly(2))
            ->method('isPublicEmailOwner')
            ->willReturnMap([
                ['PublicOwnerClass', true],
                ['PrivateOwnerClass', false]
            ]);
        $this->connection->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());
        $this->connection->expects(self::exactly(2))
            ->method('executeStatement')
            ->withConsecutive(
                [
                    'DELETE FROM oro_email_address_visibility WHERE email = ? AND organization_id NOT IN (?)',
                    ['testä@test.com', [4]],
                    [Types::STRING, Connection::PARAM_INT_ARRAY]
                ],
                [
                    'INSERT INTO oro_email_address_visibility (email, organization_id, is_visible) VALUES (?, ?, ?) '
                    . 'ON DUPLICATE KEY UPDATE is_visible = ?',
                    ['testä@test.com', 4, false, false],
                    [Types::STRING, Types::INTEGER, Types::BOOLEAN, Types::BOOLEAN]
                ]
            );
        $this->producer->expects(self::once())
            ->method('send')
            ->with(RecalculateEmailVisibilityTopic::getName(), ['email' => $emailAddress]);

        $this->manager->collectEmailAddresses([$emailAddress]);
    }

    public function testCollectEmailAddressesWithPrivateOwner(): void
    {
        $emailAddress = 'TestÄ@test.cOm';

        $this->emailOwnerProvider->expects(self::once())
            ->method('getOrganizations')
            ->with($this->em, $emailAddress)
            ->willReturn(['PrivateOwnerClass' => [3]]);
        $this->publicEmailOwnerProvider->expects(self::once())
            ->method('isPublicEmailOwner')
            ->with('PrivateOwnerClass')
            ->willReturn(false);
        $this->connection->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());
        $this->connection->expects(self::exactly(2))
            ->method('executeStatement')
            ->withConsecutive(
                [
                    'DELETE FROM oro_email_address_visibility WHERE email = ? AND organization_id NOT IN (?)',
                    ['testä@test.com', [3]],
                    [Types::STRING, Connection::PARAM_INT_ARRAY]
                ],
                [
                    'INSERT INTO oro_email_address_visibility (email, organization_id, is_visible) VALUES (?, ?, ?) '
                    . 'ON DUPLICATE KEY UPDATE is_visible = ?',
                    ['testä@test.com', 3, false, false],
                    [Types::STRING, Types::INTEGER, Types::BOOLEAN, Types::BOOLEAN]
                ]
            );
        $this->producer->expects(self::once())
            ->method('send')
            ->with(RecalculateEmailVisibilityTopic::getName(), ['email' => $emailAddress]);

        $this->manager->collectEmailAddresses([$emailAddress]);
    }
}
