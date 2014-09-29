<?php

namespace Oro\Bundle\SecurityBundle\Form\ChoiceList;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\DBAL\Connection;

use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class AclProtectedQueryBuilderLoader extends ORMQueryBuilderLoader
{
    /** @var AclHelper */
    protected $aclHelper;

    /**
     * Contains the query builder that builds the query for fetching the
     * entities
     *
     * This property should only be accessed through queryBuilder.
     *
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * {@inheritdoc}
     */
    public function __construct(AclHelper $aclHelper, $queryBuilder, $manager = null, $class = null)
    {
        if (!($queryBuilder instanceof QueryBuilder || $queryBuilder instanceof \Closure)) {
            throw new UnexpectedTypeException($queryBuilder, 'Doctrine\ORM\QueryBuilder or \Closure');
        }

        if ($queryBuilder instanceof \Closure) {
            if (!$manager instanceof EntityManager) {
                throw new UnexpectedTypeException($manager, 'Doctrine\ORM\EntityManager');
            }

            $queryBuilder = $queryBuilder($manager->getRepository($class));

            if (!$queryBuilder instanceof QueryBuilder) {
                throw new UnexpectedTypeException($queryBuilder, 'Doctrine\ORM\QueryBuilder');
            }
        }

        $this->queryBuilder = $queryBuilder;
        $this->aclHelper    = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntities()
    {
        $query = $this->queryBuilder->getQuery();

        return $this->applyQuery($query)->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntitiesByIds($identifier, array $values)
    {
        $qb        = clone ($this->queryBuilder);
        $alias     = current($qb->getRootAliases());
        $parameter = 'ORMQueryBuilderLoader_getEntitiesByIds_' . $identifier;
        $where     = $qb->expr()->in($alias . '.' . $identifier, ':' . $parameter);

        // Guess type
        $entity   = current($qb->getRootEntities());
        $metadata = $qb->getEntityManager()->getClassMetadata($entity);
        if (in_array($metadata->getTypeOfField($identifier), array('integer', 'bigint', 'smallint'))) {
            $parameterType = Connection::PARAM_INT_ARRAY;
        } else {
            $parameterType = Connection::PARAM_STR_ARRAY;
        }

        $qb->andWhere($where)->setParameter($parameter, $values, $parameterType);

        $query = $qb->getQuery();

        return $this->applyQuery($query)->getResult();
    }

    /**
     * @param Query  $query
     * @param string $permission
     * @param bool   $checkRelations
     *
     * @return Query
     */
    private function applyQuery($query, $permission = 'VIEW', $checkRelations = true)
    {
        return $this->aclHelper->apply($query, $permission, $checkRelations);
    }
}
