<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EmailBundle\Entity\Email;

/**
* Doctrine repository for EmailThread entity
*/
class EmailThreadRepository extends EntityRepository
{
    /**
     * @return int
     */
    public function deleteOrphanThreads()
    {
        $em = $this->getEntityManager();

        $subQb = $em->createQueryBuilder();
        $subQb->select('e')
            ->from(Email::class, 'e')
            ->where(
                $subQb->expr()->eq('e.thread', 'et')
            );

        $qb = $em->createQueryBuilder();

        return $qb->delete($this->getEntityName(), 'et')
            ->where(
                $qb->expr()->not(
                    $qb->expr()->exists(
                        $subQb->getQuery()->getDQL()
                    )
                )
            )
            ->getQuery()
            ->execute();
    }
}
