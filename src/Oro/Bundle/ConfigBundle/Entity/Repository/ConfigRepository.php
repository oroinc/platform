<?php

namespace Oro\Bundle\ConfigBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\ORM\Hydration\ConfigObjectHydrator;

/**
 * Repository class form Config entity.
 */
class ConfigRepository extends EntityRepository
{
    /**
     * @param string $scope
     * @param mixed  $scopeId
     *
     * @return Config|null
     */
    public function findByEntity($scope, $scopeId)
    {
        $this
            ->getEntityManager()
            ->getConfiguration()
            ->addCustomHydrationMode(ConfigObjectHydrator::class, ConfigObjectHydrator::class);

        return $this->createQueryBuilder('c')
            ->select('c, cv')
            ->leftJoin('c.values', 'cv')
            ->where('c.scopedEntity = :entityName AND c.recordId = :entityId')
            ->setParameter('entityName', $scope)
            ->setParameter('entityId', $scopeId)
            ->getQuery()
            ->getOneOrNullResult(ConfigObjectHydrator::class);
    }
}
