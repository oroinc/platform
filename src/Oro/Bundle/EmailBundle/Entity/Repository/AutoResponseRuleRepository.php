<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use DateTime;
use Doctrine\ORM\EntityRepository;

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
            ->setParameter('until', new DateTime('-1 day'))
            ->getQuery()
            ->getResult();

        array_map([$this->_em, 'remove'], $rules);
        $this->_em->flush();
    }
}
