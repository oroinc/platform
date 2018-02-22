<?php

namespace Oro\Bundle\ImapBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

/**
 * Doctrine repository for UserEmailOrigin entity
 */
class UserEmailOriginRepository extends EntityRepository
{
    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAllOriginsWithAccessTokens()
    {
        $queryBuilder = $this->createQueryBuilder('user_email_origin');
        $queryBuilder->where($queryBuilder->expr()->isNotNull('user_email_origin.accessToken'));

        return $queryBuilder;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAllOriginsWithRefreshTokens()
    {
        $queryBuilder = $this->createQueryBuilder('user_email_origin');
        $queryBuilder->where($queryBuilder->expr()->isNotNull('user_email_origin.refreshToken'));

        return $queryBuilder;
    }

    /**
     * Returns an array with origins by the given array with ids.
     *
     * @param array $originIds
     * @return UserEmailOrigin[]
     */
    public function getOriginsByIds(array $originIds)
    {
        return $this->createQueryBuilder('o')
            ->where('o.id in (:ids)')
            ->setParameter('ids', $originIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param UserEmailOrigin $origin
     * @param null|bool $syncEnabled
     * @return int
     */
    public function deleteRelatedEmails(UserEmailOrigin $origin, $syncEnabled = null)
    {
        $em = $this->getEntityManager();

        $subQb = $em->createQueryBuilder();

        $params = ['originId' => $origin->getId()];

        if ($syncEnabled !== null) {
            $params['syncEnabled'] = $syncEnabled;

            $subQb->select('ie')
                ->from(ImapEmail::class, 'ie')
                ->join('ie.imapFolder', 'ief')
                ->join('ief.folder', 'ef')
                ->where(
                    $subQb->expr()->eq('ie.email', 'e'),
                    $subQb->expr()->eq('ef.origin', ':originId'),
                    $subQb->expr()->eq('ef.syncEnabled', ':syncEnabled')
                );
        } else {
            $subQb->select('eu')
                ->from(EmailUser::class, 'eu')
                ->where(
                    $subQb->expr()->eq('eu.origin', ':originId'),
                    $subQb->expr()->eq('eu.email', 'e')
                );
        }

        $qb = $em->createQueryBuilder();

        return $qb->delete(Email::class, 'e')
            ->where(
                $qb->expr()->exists(
                    $subQb->getQuery()->getDQL()
                )
            )
            ->getQuery()
            ->execute($params);
    }
}
