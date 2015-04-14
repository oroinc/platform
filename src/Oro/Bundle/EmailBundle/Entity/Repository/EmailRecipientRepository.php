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
        $queryBuilder = $this->createQueryBuilder('er')
            ->select('er')
            ->leftJoin('er.email', 'e')
            ->andWhere('e.thread = :thread')
            ->setParameter('thread', $thread);
        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }
}
