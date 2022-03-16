<?php

namespace Oro\Bundle\UserBundle\Entity\Provider;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
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
    public function getEmailOwnerClass()
    {
        return User::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findEmailOwner(EntityManager $em, $email)
    {
        /** @var User|null $user */
        $user = $em->getRepository(User::class)->findOneBy(['emailLowercase' => mb_strtolower($email)]);
        if (null === $user) {
            $qb = $em->createQueryBuilder()
                ->from(Email::class, 'ue')
                ->select('ue')
                ->setParameter('email', $email);
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
    public function getOrganizations(EntityManager $em, $email)
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
    public function getEmails(EntityManager $em, $organizationId)
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
