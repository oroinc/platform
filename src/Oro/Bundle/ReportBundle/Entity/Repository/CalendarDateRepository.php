<?php

namespace Oro\Bundle\ReportBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ReportBundle\Entity\CalendarDate;

class CalendarDateRepository extends EntityRepository
{
    /**
     * @param \DateTime|null $date
     * @return CalendarDate
     */
    public function getDate(\DateTime $date = null)
    {
        $qb = $this->createQueryBuilder('d')->orderBy('d.date', 'DESC')->setMaxResults(1);
        if ($date) {
            $qb->andWhere('d.date LIKE :date')->setParameter('date', $date->format('Y-m-d') . "%");
        }

        return $qb->getQuery()->getSingleResult();
    }
}
