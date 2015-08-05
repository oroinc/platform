<?php

namespace Oro\Bundle\SecurityBundle\Form\ChoiceList;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\DBAL\Connection;

use Symfony\Bridge\Doctrine\Form\ChoiceList\EntityLoaderInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class AclProtectedQueryBuilderLoader implements EntityLoaderInterface
{
    /** @var AclHelper */
    protected $aclHelper;

    /** @var QueryBuilder */
    protected $queryBuilder;

    /** @var string */
    protected $permission;

    /** @var bool */
    protected $checkRelations;

    /**
     * @param AclHelper $aclHelper
     * @param           $queryBuilder
     * @param null      $manager
     * @param null      $class
     * @param string    $permission
     * @param bool      $checkRelations
     */
    public function __construct(
        AclHelper $aclHelper,
        $queryBuilder,
        $manager = null,
        $class = null,
        $permission = 'VIEW',
        $checkRelations = false
    ) {
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

        $this->queryBuilder   = $queryBuilder;
        $this->aclHelper      = $aclHelper;
        $this->permission     = $permission;
        $this->checkRelations = $checkRelations;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntities()
    {
        $query = $this->queryBuilder->getQuery();

        return $this->applyACL($query)->execute();
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
        if (in_array($metadata->getTypeOfField($identifier), ['integer', 'bigint', 'smallint'])) {
            $parameterType = Connection::PARAM_INT_ARRAY;

            // the same workaround as in Symfony:
            // {@see \Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader::getEntitiesByIds }
            // Filter out non-integer values (e.g. ""). If we don't, some
            // databases such as PostgreSQL fail.
            $values = array_values(array_filter($values, function ($v) {
                return (string) $v === (string) (int) $v;
            }));
        } else {
            $parameterType = Connection::PARAM_STR_ARRAY;
        }

        $qb->andWhere($where)->setParameter($parameter, $values, $parameterType);

        $query = $qb->getQuery();

        return $this->applyACL($query)->getResult();
    }

    /**
     * @param Query $query
     *
     * @return Query
     */
    protected function applyACL($query)
    {
        return $this->aclHelper->apply($query, $this->permission, $this->checkRelations);
    }
}
