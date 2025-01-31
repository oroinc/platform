<?php

namespace Oro\Bundle\ReportBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ReportBundle\Entity\CalendarDate;

/**
 * Doctrine repository for CalendarDate entity.
 */
class CalendarDateRepository extends EntityRepository
{
    /**
     * @param \DateTime|null $date
     * @return CalendarDate|null
     */
    public function getDate(?\DateTime $date = null)
    {
        $qb = $this->createQueryBuilder('d')->orderBy('d.date', 'DESC')->setMaxResults(1);
        if ($date) {
            $qb
                ->where($qb->expr()->eq('d.date', ':date'))
                ->setParameter('date', $date, Types::DATE_MUTABLE);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
