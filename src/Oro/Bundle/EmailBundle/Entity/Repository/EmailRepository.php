<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EmailBundle\Entity\Email;

class EmailRepository extends EntityRepository
{
    /**
     * Gets emails by ids
     *
     * @param array $ids
     *
     * @return array
     */
    public function findEmailsByIds($ids)
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $criteria     = new Criteria();
        $criteria->where(Criteria::expr()->in('id', $ids));
        $criteria->orderBy(['sentAt' => Criteria::DESC]);
        $queryBuilder->addCriteria($criteria);
        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }

    /**
     * Gets email by Message-ID
     *
     * @param string $messageId
     *
     * @return Email|null
     */
    public function findEmailByMessageId($messageId)
    {
        return $this->createQueryBuilder('e')
            ->where('e.messageId = :messageId')
            ->setParameter('messageId', $messageId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
