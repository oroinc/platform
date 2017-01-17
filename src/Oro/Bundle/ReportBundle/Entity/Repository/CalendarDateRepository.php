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
            $from = new \DateTime($date->format("Y-m-d") . " 00:00:01");
            $to   = new \DateTime($date->format("Y-m-d") . " 23:59:59");
            $qb
                ->andWhere($qb->expr()->between('d.date', ':from', ':to'))
                ->setParameter('from', $from)
                ->setParameter('to', $to);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
