<?php

namespace Oro\Bundle\EmailBundle\Api\Repository;

use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides an email origin that should be used for emails created via API.
 */
class EmailOriginRepository implements ResetInterface
{
    private DoctrineHelper $doctrineHelper;
    private array $emailOrigins = [];

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Gets an email origin that should be used for emails created via API.
     * This method checks that API email origin exists in the database and create it if it does not exist yet.
     */
    public function getEmailOrigin(int $organizationId, int $userId): EmailOrigin
    {
        $cacheKey = sprintf('%d_%d', $organizationId, $userId);
        if (isset($this->emailOrigins[$cacheKey])) {
            return $this->emailOrigins[$cacheKey];
        }

        $emailOriginName = InternalEmailOrigin::BAP . '_API';
        /** @var EntityManagerInterface $em */
        $em = $this->doctrineHelper->getEntityManagerForClass(InternalEmailOrigin::class);
        $emailOrigin = $this->findEmailOrigin($em, $organizationId, $userId, $emailOriginName);
        if (null === $emailOrigin) {
            $emailOrigin = $this->createEmailOrigin($em, $organizationId, $userId, $emailOriginName);
        }
        $this->emailOrigins[$cacheKey] = $emailOrigin;

        return $emailOrigin;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->emailOrigins = [];
    }

    private function findEmailOrigin(
        EntityManagerInterface $em,
        int $organizationId,
        int $userId,
        string $emailOriginName
    ): ?InternalEmailOrigin {
        return $em->getRepository(InternalEmailOrigin::class)->findOneBy([
            'organization' => $organizationId,
            'owner'        => $userId,
            'internalName' => $emailOriginName
        ]);
    }

    private function createEmailOrigin(
        EntityManagerInterface $em,
        int $organizationId,
        int $userId,
        string $emailOriginName
    ): InternalEmailOrigin {
        $metadata = $em->getClassMetadata(InternalEmailOrigin::class);
        $discriminatorValue = $this->getDiscriminatorValue($metadata);
        $sql = sprintf(
            'INSERT INTO %1$s (%2$s, mailbox_name, internal_name, organization_id, owner_id, isActive) SELECT '
            . '(CASE WHEN EXISTS (SELECT 1 FROM %1$s WHERE %2$s = ? AND internal_name = ?'
            . ' AND organization_id = ? AND owner_id = ?) THEN NULL ELSE ? END), ?, ?, ?, ?, ?',
            $metadata->getTableName(),
            $this->getDiscriminatorColumnName($metadata)
        );
        $params = [
            $discriminatorValue,
            $emailOriginName,
            $organizationId,
            $userId,
            $discriminatorValue,
            InternalEmailOrigin::MAILBOX_NAME,
            $emailOriginName,
            $organizationId,
            $userId,
            true
        ];
        $types = [
            Types::STRING,
            Types::STRING,
            Types::INTEGER,
            Types::INTEGER,
            Types::STRING,
            Types::STRING,
            Types::STRING,
            Types::INTEGER,
            Types::INTEGER,
            Types::BOOLEAN
        ];

        $createException = null;
        try {
            $em->getConnection()->executeStatement($sql, $params, $types);
        } catch (NotNullConstraintViolationException $e) {
            // it is expected exception when the email origin already exists
            $createException = $e;
        }

        $emailOrigin = $this->findEmailOrigin($em, $organizationId, $userId, $emailOriginName);
        if (null === $emailOrigin) {
            throw new RuntimeException(
                sprintf('The email origin "%s" does not exist.', $emailOriginName),
                0,
                $createException
            );
        }

        return $emailOrigin;
    }

    private function getDiscriminatorColumnName(ClassMetadata $metadata): string
    {
        $info = $metadata->getDiscriminatorColumn();

        return $info['name'];
    }

    private function getDiscriminatorValue(ClassMetadata $metadata): string
    {
        $map = array_flip($metadata->discriminatorMap);

        return $map[InternalEmailOrigin::class];
    }
}
