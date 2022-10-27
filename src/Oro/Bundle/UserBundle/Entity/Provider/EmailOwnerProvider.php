<?php

namespace Oro\Bundle\UserBundle\Entity\Provider;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface;
use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * The email address owner provider for User entity.
 */
class EmailOwnerProvider implements EmailOwnerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEmailOwnerClass(): string
    {
        return User::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findEmailOwner(EntityManagerInterface $em, string $email): ?EmailOwnerInterface
    {
        /** @var User|null $user */
        $results = $em->getRepository(User::class)->findBy(['emailLowercase' => mb_strtolower($email)], null, 1);
        $user = array_shift($results);
        if (null === $user) {
            $qb = $em->createQueryBuilder()
                ->from(Email::class, 'ue')
                ->select('ue')
                ->setParameter('email', $email)
                ->setMaxResults(1);
            if ($em->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform) {
                $qb->where('LOWER(ue.email) = LOWER(:email)');
            } else {
                $qb->where('ue.email = :email');
            }
            /** @var Email|null $emailEntity */
            $emailEntity = $qb->getQuery()->getOneOrNullResult();
            if (null !== $emailEntity) {
                $user = $emailEntity->getUser();
            }
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganizations(EntityManagerInterface $em, string $email): array
    {
        $result = [];

        $rows = $em->createQueryBuilder()
            ->from(User::class, 'u')
            ->select('o.id')
            ->join('u.organizations', 'o')
            ->where('u.emailLowercase = :email')
            ->setParameter('email', mb_strtolower($email))
            ->getQuery()
            ->getArrayResult();
        foreach ($rows as $row) {
            $result[] = (int)$row['id'];
        }

        $qb = $em->createQueryBuilder()
            ->from(Email::class, 'ue')
            ->select('o.id')
            ->join('ue.user', 'u')
            ->join('u.organizations', 'o')
            ->setParameter('email', $email);
        if ($em->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $qb->where('LOWER(ue.email) = LOWER(:email)');
        } else {
            $qb->where('ue.email = :email');
        }
        $rows = $qb->getQuery()->getArrayResult();
        foreach ($rows as $row) {
            $result[] = (int)$row['id'];
        }

        if ($result) {
            $result = array_values(array_unique($result));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmails(EntityManagerInterface $em, int $organizationId): iterable
    {
        $qb = $em->createQueryBuilder()
            ->from(User::class, 'u')
            ->select('u.email')
            ->where('u.organization = :organizationId')
            ->setParameter('organizationId', $organizationId)
            ->orderBy('u.id');
        $iterator = new BufferedQueryResultIterator($qb);
        $iterator->setBufferSize(5000);
        foreach ($iterator as $row) {
            yield $row['email'];
        }

        $qb = $em->createQueryBuilder()
            ->from(Email::class, 'ue')
            ->select('ue.email')
            ->join('ue.user', 'u')
            ->where('u.organization = :organizationId')
            ->setParameter('organizationId', $organizationId)
            ->orderBy('ue.id');
        $iterator = new BufferedQueryResultIterator($qb);
        $iterator->setBufferSize(5000);
        foreach ($iterator as $row) {
            yield $row['email'];
        }
    }
}
