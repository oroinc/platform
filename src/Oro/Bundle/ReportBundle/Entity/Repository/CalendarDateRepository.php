<?php

namespace Oro\Bundle\ReportBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ReportBundle\Entity\CalendarDate;

class CalendarDateRepository extends EntityRepository
{
    /**
     * @param \DateTime|null $date
     * @return CalendarDate|null
     */
    public function getDate(\DateTime $date = null)
    {
        $qb = $this->createQueryBuilder('d')->orderBy('d.date', 'DESC')->setMaxResults(1);
        if ($date) {
            $qb
                ->where($qb->expr()->eq('CAST(d.date as date)', 'CAST(:date as date)'))
                ->setParameter('date', $date);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
