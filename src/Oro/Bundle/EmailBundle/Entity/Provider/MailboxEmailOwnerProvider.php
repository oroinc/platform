<?php

namespace Oro\Bundle\EmailBundle\Entity\Provider;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\Mailbox;

/**
 * The email address owner provider for Mailbox entity.
 */
class MailboxEmailOwnerProvider implements EmailOwnerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEmailOwnerClass(): string
    {
        return Mailbox::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findEmailOwner(EntityManagerInterface $em, string $email): ?EmailOwnerInterface
    {
        $qb = $em->createQueryBuilder()
            ->from(Mailbox::class, 'mb')
            ->select('mb')
            ->setParameter('email', $email);
        if ($em->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $qb->where('LOWER(mb.email) = LOWER(:email)');
        } else {
            $qb->where('mb.email = :email');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganizations(EntityManagerInterface $em, string $email): array
    {
        $qb = $em->createQueryBuilder()
            ->from(Mailbox::class, 'mb')
            ->select('IDENTITY(mb.organization) AS id')
            ->setParameter('email', $email);
        if ($em->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $qb->where('LOWER(mb.email) = LOWER(:email)');
        } else {
            $qb->where('mb.email = :email');
        }
        $rows = $qb->getQuery()->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (int)$row['id'];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmails(EntityManagerInterface $em, int $organizationId): iterable
    {
        $qb = $em->createQueryBuilder()
            ->from(Mailbox::class, 'mb')
            ->select('mb.email')
            ->where('mb.organization = :organizationId')
            ->setParameter('organizationId', $organizationId)
            ->orderBy('mb.id');
        $iterator = new BufferedQueryResultIterator($qb);
        $iterator->setBufferSize(5000);
        foreach ($iterator as $row) {
            yield $row['email'];
        }
    }
}
