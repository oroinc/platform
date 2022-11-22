<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\Topic\RecalculateEmailVisibilityTopic;
use Oro\Bundle\EmailBundle\Entity\EmailAddressVisibility;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\EmailBundle\Entity\Provider\PublicEmailOwnerProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * The manager that have a set of methods to work with email visibility.
 */
class EmailAddressVisibilityManager
{
    private EmailOwnerProvider $emailOwnerProvider;
    private ManagerRegistry $doctrine;
    private MessageProducerInterface $producer;
    private PublicEmailOwnerProvider $publicEmailOwnerProvider;

    private string $emailVisibilityTableName = '';

    public function __construct(
        EmailOwnerProvider $emailOwnerProvider,
        ManagerRegistry $doctrine,
        MessageProducerInterface $producer,
        PublicEmailOwnerProvider $publicEmailOwnerProvider
    ) {
        $this->emailOwnerProvider = $emailOwnerProvider;
        $this->doctrine = $doctrine;
        $this->producer = $producer;
        $this->publicEmailOwnerProvider = $publicEmailOwnerProvider;
    }

    /**
     * Refreshes the email user entity visibility by the email addresses.
     */
    public function processEmailUserVisibility(EmailUser $emailUser): void
    {
        $email = $emailUser->getEmail();
        $emails = [$email->getFromEmailAddress()->getEmail()];
        foreach ($email->getRecipients() as $recipient) {
            if (EmailRecipient::BCC === $recipient->getType()) {
                continue;
            }
            $emails[] = $recipient->getEmailAddress()->getEmail();
        }

        $emailUser->setIsEmailPrivate(!$this->isEmailPublic($emails, $emailUser->getOrganization()->getId()));
    }

    /**
     * Collects and saves visibility data for the given email addresses.
     */
    public function collectEmailAddresses(array $emails): void
    {
        foreach ($emails as $email) {
            $this->collectEmailAddressOnUpdate($email);
        }
    }

    /**
     * Updates the visibility for the given email address in the given organization.
     */
    public function updateEmailAddressVisibility(string $emailAddress, int $organizationId, bool $visible): void
    {
        $emailAddress = mb_strtolower($emailAddress);
        $em = $this->getEntityManager();
        $connection = $em->getConnection();
        $sql = sprintf(
            'INSERT INTO %s (email, organization_id, is_visible) VALUES (?, ?, ?) ',
            $this->getEmailVisibilityTableName()
        );
        if ($connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $sql .= 'ON DUPLICATE KEY UPDATE is_visible = ?';
        } else {
            $sql .= 'ON CONFLICT (email, organization_id) DO UPDATE SET is_visible = ?';
        }

        $connection->executeStatement(
            $sql,
            [$emailAddress, $organizationId, $visible, $visible],
            [Types::STRING, Types::INTEGER, Types::BOOLEAN, Types::BOOLEAN]
        );
    }

    /**
     * Updates the visibility for all email addresses in the given organization.
     */
    public function updateEmailAddressVisibilities(int $organizationId): void
    {
        $emailAddresses = $this->emailOwnerProvider->getEmails(
            $this->getEntityManager(),
            $organizationId
        );
        foreach ($emailAddresses as [$emailAddress, $emailAddressOwnerClass]) {
            $this->updateEmailAddressVisibility(
                $emailAddress,
                $organizationId,
                $this->publicEmailOwnerProvider->isPublicEmailOwner($emailAddressOwnerClass)
            );
        }
    }

    /**
     * Collects and saves visibility data for the given email address.
     */
    private function collectEmailAddressOnUpdate(string $emailAddress): void
    {
        $ownersOrganizations = $this->emailOwnerProvider->getOrganizations($this->getEntityManager(), $emailAddress);
        $visibilityPerOrganizations = [];
        foreach ($ownersOrganizations as $ownerClass => $organizations) {
            $isPublicEmailAddress = $this->publicEmailOwnerProvider->isPublicEmailOwner($ownerClass);
            foreach ($organizations as $organizationId) {
                if (!isset($visibilityPerOrganizations[$organizationId])) {
                    $visibilityPerOrganizations[$organizationId] = $isPublicEmailAddress;
                } elseif (!$isPublicEmailAddress && true === $visibilityPerOrganizations[$organizationId]) {
                    $visibilityPerOrganizations[$organizationId] = false;
                }
            }
        }

        $this->removeEmailVisibilityForUnknownOrganizations($emailAddress, array_keys($visibilityPerOrganizations));
        foreach ($visibilityPerOrganizations as $organizationId => $visible) {
            $this->updateEmailAddressVisibility($emailAddress, $organizationId, $visible);
        }

        $this->producer->send(RecalculateEmailVisibilityTopic::getName(), ['email' => $emailAddress]);
    }

    private function isEmailPublic(array $emailAddresses, int $organizationId): bool
    {
        $em = $this->getEntityManager();
        $lowercaseEmailAddresses = array_values(array_unique(array_map(
            fn (string $emailAddress) => mb_strtolower($emailAddress),
            $emailAddresses
        )));

        $result = $em->getConnection()->fetchOne(
            sprintf(
                'SELECT 1 FROM %s WHERE is_visible = ? AND organization_id = ? AND email IN (?) LIMIT 1',
                $this->getEmailVisibilityTableName()
            ),
            [true, $organizationId, $lowercaseEmailAddresses],
            [Types::BOOLEAN, Types::INTEGER, Connection::PARAM_STR_ARRAY]
        );

        return (bool)$result;
    }

    private function removeEmailVisibilityForUnknownOrganizations(
        string $emailAddress,
        array $visibilityPerOrganizations
    ): void {
        $emailAddress = mb_strtolower($emailAddress);
        $em = $this->getEntityManager();
        $tableName = $this->getEmailVisibilityTableName();
        if (!empty($visibilityPerOrganizations)) {
            $em->getConnection()->executeStatement(
                sprintf('DELETE FROM %s WHERE email = ? AND organization_id NOT IN (?)', $tableName),
                [$emailAddress, $visibilityPerOrganizations],
                [Types::STRING, Connection::PARAM_INT_ARRAY]
            );
        } else {
            $em->getConnection()->executeStatement(
                sprintf('DELETE FROM %s WHERE email = ?', $tableName),
                [$emailAddress],
                [Types::STRING]
            );
        }
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(EmailAddressVisibility::class);
    }

    private function getEmailVisibilityTableName(): string
    {
        if ('' === $this->emailVisibilityTableName) {
            $this->emailVisibilityTableName = $this->getEntityManager()
                ->getClassMetadata(EmailAddressVisibility::class)->getTableName();
        }

        return $this->emailVisibilityTableName;
    }
}
