<?php

namespace Oro\Bundle\UserBundle\Autocomplete;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

class WidgetRoleSearchHandler extends SearchHandler
{
    /**
     * {@inheritdoc}
     */
    protected function getEntitiesByIds(array $entityIds)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityRepository->createQueryBuilder('e');
        $queryBuilder->where($queryBuilder->expr()->in('e.' . $this->idFieldName, $entityIds));

        $queryBuilder->andWhere($queryBuilder->expr()->like('e.role', ':prefix'));
        $queryBuilder->setParameter('prefix', Role::PREFIX_ROLE . '%');

        return $queryBuilder->getQuery()->getResult();
    }
}
