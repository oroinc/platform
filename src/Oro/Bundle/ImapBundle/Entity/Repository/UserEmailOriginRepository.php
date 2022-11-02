<?php

namespace Oro\Bundle\ImapBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

/**
 * Doctrine repository for UserEmailOrigin entity
 */
class UserEmailOriginRepository extends EntityRepository
{
    /**
     * @param string $type
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAllOriginsWithAccessTokens(string $type = null)
    {
        $queryBuilder = $this->createQueryBuilder('user_email_origin');
        $queryBuilder->where($queryBuilder->expr()->isNotNull('user_email_origin.accessToken'));
        if (null !== $type) {
            $queryBuilder
                ->andWhere('user_email_origin.accountType = :typeName')
                ->setParameter('typeName', $type);
        }

        return $queryBuilder;
    }

    /**
     * @param string $type
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAllOriginsWithRefreshTokens(string $type = null)
    {
        $queryBuilder = $this->createQueryBuilder('user_email_origin');
        $queryBuilder->where($queryBuilder->expr()->isNotNull('user_email_origin.refreshToken'));
        if (null !== $type) {
            $queryBuilder
                ->andWhere('user_email_origin.accountType = :typeName')
                ->setParameter('typeName', $type);
        }

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

    public function getEmailIdsFromDisabledFoldersIterator(EmailOrigin $origin): BufferedQueryResultIterator
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('IDENTITY(ie.email) as id, ie')
            ->from(ImapEmail::class, 'ie')
            ->join('ie.imapFolder', 'ief')
            ->join('ief.folder', 'ef')
            ->andWhere('ef.origin =:originId')
            ->setParameter('originId', $origin->getId());

        if ($origin->isActive()) {
            $qb->andWhere('ef.syncEnabled = :syncEnabled')
                ->setParameter('syncEnabled', false);
        }

        $iterator = new BufferedQueryResultIterator($qb);
        $iterator->setBufferSize(2000);

        return $iterator;
    }
}
