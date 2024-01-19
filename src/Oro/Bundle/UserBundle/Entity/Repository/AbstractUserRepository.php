<?php

namespace Oro\Bundle\UserBundle\Entity\Repository;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * Abstract class for doctrine repository which will be used in user manager.
 */
class AbstractUserRepository extends EntityRepository
{
    /**
     * @param string $field
     * @return bool
     */
    public function isCaseInsensitiveCollation($field = 'email'): bool
    {
        if (!$this->isMySql()) {
            return false;
        }

        $connection = $this->getEntityManager()
            ->getConnection();

        return (bool) $connection->fetchAll(
            'SELECT 1
            FROM information_schema.columns
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
            AND COLLATION_NAME LIKE ?
            LIMIT 1;',
            [
                $connection->getDatabase(),
                $this->getClassMetadata()->getTableName(),
                $field,
                '%_ci'
            ]
        );
    }

    public function findUserByEmail(string $email, bool $useLowercase = false): ?AbstractUser
    {
        return $this->getQbForFindUserByEmail($email, $useLowercase)
            ->getQuery()
            ->getOneOrNullResult();
    }

    protected function getQbForFindUserByEmail(string $email, bool $useLowercase): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');
        $qb->setMaxResults(1);

        if ($useLowercase) {
            $qb->where($qb->expr()->eq('u.emailLowercase', ':email'))
                ->setParameter('email', mb_strtolower($email));
        } else {
            $qb->where(
                $qb->expr()->eq(
                    $this->isCaseInsensitiveCollation() ? 'CAST(u.email as binary)' : 'u.email',
                    ':email'
                )
            )
            ->setParameter('email', $email);
        }

        return $qb;
    }

    public function findLowercaseDuplicatedEmails(int $limit): array
    {
        $result = $this->getQbForFindLowercaseDuplicatedEmails($limit)
            ->getQuery()
            ->getArrayResult();

        return array_column($result, 'emailLowercase');
    }

    protected function getQbForFindLowercaseDuplicatedEmails(int $limit): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.emailLowercase')
            ->groupBy('u.emailLowercase', 'u.organization')
            ->having($qb->expr()->gt('COUNT(u.id)', 1))
            ->setMaxResults($limit);

        return $qb;
    }

    protected function isMySql(): bool
    {
        $platform = $this->getEntityManager()
            ->getConnection()
            ->getDatabasePlatform();

        return $platform instanceof MySqlPlatform;
    }
}
