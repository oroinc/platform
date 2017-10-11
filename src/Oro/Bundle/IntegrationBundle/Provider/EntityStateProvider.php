<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\State;

class EntityStateProvider
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Do select entities/ids from entity table, join oro_integration_entity_state on class = 'enity_class'
     * and id In (:ids) where status = 1 # scheduled_for_export
     *
     * @param string $entityClass
     * @param int[] $ids
     * @param int $state
     *
     * @return array
     */
    public function checkEntityState($entityClass, $ids, $state)
    {
        $queryBuilder = $this->getEntityManager($entityClass)->createQueryBuilder();
        $queryBuilder->select('e');
        $queryBuilder->from($entityClass, 'e');
        $queryBuilder->leftJoin(
            State::class,
            'state',
            Join::WITH,
            'state.entity_class = ' . $entityClass . ' AND state.entity_id = e.id'
        );
        $queryBuilder->where('e.id IN :ids');
        $queryBuilder->andWhere('state.state = :state');
        $queryBuilder->setParameters(
            [
                ':ids' => $ids,
                ':state' => $state,
            ]
        );

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string $entityClass
     * @return EntityManagerInterface|ObjectManager
     */
    protected function getEntityManager($entityClass)
    {
        return $this->doctrineHelper->getEntityManagerForClass($entityClass);
    }
}
