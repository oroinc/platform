<?php

namespace Oro\Bundle\SecurityBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class AclSecurityIdentityRepository extends EntityRepository
{
    /**
     * @param string $identifier
     * @param bool $username
     *
     * @return bool
     */
    public function hasAclEntry($identifier, $username)
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->select('e.id')
            ->join(
                'Oro\Bundle\SecurityBundle\Entity\AclEntry',
                'e',
                Join::WITH,
                'e.securityIdentity = s.id'
            )
            ->where('s.identifier = :identifier')
            ->andWhere('s.username = :username')
            ->setParameter('identifier', $identifier)
            ->setParameter('username', $username);
        $queryBuilder->setMaxResults(1);

        return (bool) $queryBuilder->getQuery()->getResult();
    }
}
