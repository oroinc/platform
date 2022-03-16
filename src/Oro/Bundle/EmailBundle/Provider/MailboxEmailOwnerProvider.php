<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface;

/**
 * The email address owner provider for Mailbox entity.
 */
class MailboxEmailOwnerProvider implements EmailOwnerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEmailOwnerClass()
    {
        return Mailbox::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findEmailOwner(EntityManager $em, $email)
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
    public function getOrganizations(EntityManager $em, $email)
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
    public function getEmails(EntityManager $em, $organizationId)
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
