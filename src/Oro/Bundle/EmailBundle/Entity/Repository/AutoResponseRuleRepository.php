<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;

/**
 * Doctrine repository for AutoResponseRule entity.
 */
class AutoResponseRuleRepository extends EntityRepository
{
    /**
     * Clears old unassigned auto responses
     */
    public function clearAutoResponses()
    {
        $rules = $this->createQueryBuilder('r')
            ->andWhere('r.mailbox IS NULL')
            ->andWhere('r.createdAt < :until')
            ->setParameter('until', new \DateTime('-1 day'), Types::DATETIME_MUTABLE)
            ->getQuery()
            ->getResult();

        array_map([$this->_em, 'remove'], $rules);
        $this->_em->flush();
    }
}
