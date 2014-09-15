<?php

namespace Oro\Bundle\ImapBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

class ImapEmailFolderRepository extends EntityRepository
{
    /**
     * @param EmailOrigin $origin
     * @param string      $alias
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getFoldersByOriginQueryBuilder(EmailOrigin $origin, $alias = 'ifo')
    {
        return $this->createQueryBuilder($alias)
            ->leftJoin($alias . '.folder', 'f')
            ->where('f.origin = ?0')
            ->orderBy('f.name')
            ->setParameter(0, $origin);
    }

    /**
     * @param EmailOrigin $origin
     * @param bool        $filterOutdated
     *
     * @return array
     */
    public function getFoldersByOrigin(EmailOrigin $origin, $filterOutdated = true)
    {
        $queryBuilder = $this->getFoldersByOriginQueryBuilder($origin, 'ifo');
        if ($filterOutdated) {
            $queryBuilder->andWhere('f.outdatedAt IS NULL');
        }

        return $queryBuilder->getQuery()->getResult();
    }
}

