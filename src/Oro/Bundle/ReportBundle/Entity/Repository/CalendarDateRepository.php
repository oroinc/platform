<?php

namespace Oro\Bundle\ReportBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class CalendarDateRepository extends EntityRepository
{
    public function getDate(\DateTime $date = null)
    {
        $qb = $this->createQueryBuilder('d');
        $qb->orderBy('d.date', 'DESC')
            ->setMaxResults(1);

        if ($date) {
            $qb->andWhere('d.date LIKE :date')
                ->setParameter('date', $date->format('Y-m-d') . "%");
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
