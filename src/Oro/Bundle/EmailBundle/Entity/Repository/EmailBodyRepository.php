<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EmailBundle\Entity\Email;

/**
 * Doctrine repository for EmailBody entity
 */
class EmailBodyRepository extends EntityRepository
{
    /**
     * @return int
     */
    public function deleteOrphanBodies()
    {
        $em = $this->getEntityManager();

        $subQb = $em->createQueryBuilder();
        $subQb->select('e')
            ->from(Email::class, 'e')
            ->where(
                $subQb->expr()->eq('e.emailBody', 'eb')
            );

        $qb = $em->createQueryBuilder();

        return $qb->delete($this->getEntityName(), 'eb')
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
