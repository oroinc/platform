<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\EmailThread;

class EmailRecipientRepository extends EntityRepository
{
    /**
     * Get recipients in thread of current one
     *
     * @param EmailThread $thread
     *
     * @return EmailRecipient[]
     */
    public function getThreadUniqueRecipients(EmailThread $thread)
    {
        $filterQuery = $this->createQueryBuilder('ef')
            ->select('MIN(ef.id)')
            ->leftJoin('ef.email', 'em')
            ->andWhere('em.thread = :thread')
            ->groupBy('ef.emailAddress');
        $queryBuilder = $this->createQueryBuilder('er');
        $queryBuilder
            ->andWhere($queryBuilder->expr()->in('er.id', $filterQuery->getDQL()))
            ->setParameter('thread', $thread);
        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }
}
