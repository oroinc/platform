<?php

namespace Oro\Bundle\SecurityBundle\Form\ChoiceList;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Bridge\Doctrine\Form\ChoiceList\EntityLoaderInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * The loader for entities in the choice list that protects loaded data by ACL.
 */
class AclProtectedQueryBuilderLoader implements EntityLoaderInterface
{
    protected AclHelper $aclHelper;
    protected QueryBuilder $queryBuilder;
    protected string $permission;
    protected array $options = [];

    public function __construct(
        AclHelper $aclHelper,
        QueryBuilder|\Closure $queryBuilder,
        ?EntityManagerInterface $em = null,
        ?string $class = null,
        string $permission = 'VIEW',
        array $options = []
    ) {
        if ($queryBuilder instanceof \Closure) {
            if (!$em instanceof EntityManagerInterface) {
                throw new UnexpectedTypeException($em, EntityManagerInterface::class);
            }

            $queryBuilder = $queryBuilder($em->getRepository($class));
            if (!$queryBuilder instanceof QueryBuilder) {
                throw new UnexpectedTypeException($queryBuilder, QueryBuilder::class);
            }
        }

        $this->queryBuilder = $queryBuilder;
        $this->aclHelper = $aclHelper;
        $this->permission = $permission;
        $this->options = $options;
    }

    #[\Override]
    public function getEntities(): array
    {
        $query = $this->queryBuilder->getQuery();

        return $this->aclHelper->apply($query, $this->permission, $this->options)->execute();
    }

    #[\Override]
    public function getEntitiesByIds($identifier, array $values): array
    {
        QueryBuilderUtil::checkIdentifier($identifier);
        $qb = clone($this->queryBuilder);
        $alias = current($qb->getRootAliases());
        $parameter = 'ORMQueryBuilderLoader_getEntitiesByIds_' . $identifier;
        $where = $qb->expr()->in($alias . '.' . $identifier, ':' . $parameter);

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

        return $this->aclHelper->apply($query, $this->permission, $this->options)
            ->getResult();
    }
}
