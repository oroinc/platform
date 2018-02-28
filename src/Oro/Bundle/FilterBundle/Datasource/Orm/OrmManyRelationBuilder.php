<?php

namespace Oro\Bundle\FilterBundle\Datasource\Orm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\ManyRelationBuilderInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

class OrmManyRelationBuilder implements ManyRelationBuilderInterface
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(FilterDatasourceAdapterInterface $ds)
    {
        return $ds instanceof OrmFilterDatasourceAdapter;
    }

    /**
     * {@inheritdoc}
     */
    public function buildComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        $fieldName,
        $parameterName,
        $filterName,
        $inverse = false
    ) {
        QueryBuilderUtil::checkIdentifier($parameterName);
        list($entity, $alias, $field) = $this->getFilterParts($ds, $fieldName);

        $rootAlias = sprintf('filter_%s', $ds->generateParameterName($filterName));
        $relAlias  = sprintf('filter_%s_rel', $ds->generateParameterName($filterName));

        $qb = $this->createSubQueryBuilder($ds, $entity, $rootAlias, $field, $relAlias, 'INNER');
        $qb->where($ds->expr()->in($relAlias, $parameterName, true));

        return $inverse
            ? $ds->expr()->notIn($alias, $qb->getDQL())
            : $ds->expr()->in($alias, $qb->getDQL());
    }

    /**
     * {@inheritdoc}
     */
    public function buildNullValueExpr(
        FilterDatasourceAdapterInterface $ds,
        $fieldName,
        $filterName,
        $inverse = false
    ) {
        QueryBuilderUtil::checkIdentifier($filterName);
        list($entity, $alias, $field) = $this->getFilterParts($ds, $fieldName);

        $rootAlias = sprintf('null_filter_%s', $filterName);
        $relAlias  = sprintf('null_filter_%s_rel', $filterName);

        $qb = $this->createSubQueryBuilder($ds, $entity, $rootAlias, $field, $relAlias, 'LEFT');
        $qb->where($inverse ? $ds->expr()->isNotNull($relAlias) : $ds->expr()->isNull($relAlias));

        return $ds->expr()->in($alias, $qb->getDQL());
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @param string                     $fieldName
     *
     * @return array [entity, alias, field]
     *
     * @throws \RuntimeException
     */
    protected function getFilterParts(OrmFilterDatasourceAdapter $ds, $fieldName)
    {
        $fieldParts = explode('.', $fieldName);
        if (count($fieldParts) !== 2) {
            throw new \RuntimeException(
                sprintf('It is expected that $fieldName is in "alias.name" format, but "%s" given.', $fieldName)
            );
        }

        $qb = $ds->getQueryBuilder();

        $entity = $this->getRootEntity($qb, $fieldParts[0]);
        if (empty($entity)) {
            $associations = [];
            $entity       = $this->findEntityByAlias($qb, $fieldParts[0]);
            while (!empty($entity) && strpos($entity, ':') === false && strpos($entity, '\\') === false) {
                $parts = explode('.', $entity);
                array_unshift($associations, $parts[1]);
                $entity = $this->findEntityByAlias($qb, $parts[0]);
            };
            if (empty($entity)) {
                throw new \RuntimeException(
                    sprintf('Cannot find root entity for "$s". It seems that a query is not valid.', $fieldName)
                );
            }

            foreach ($associations as $assoc) {
                $entity = $this->doctrine->getManagerForClass($entity)
                    ->getClassMetadata($entity)
                    ->getAssociationTargetClass($assoc);
            }
        }

        if (empty($entity)) {
            throw new \RuntimeException(
                sprintf('Cannot find entity for "$s". It seems that a query is not valid.', $fieldName)
            );
        }

        return [$entity, $fieldParts[0], $fieldParts[1]];
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $alias
     * @return null|string
     */
    protected function findEntityByAlias(QueryBuilder $qb, $alias)
    {
        $result = $this->getRootEntity($qb, $alias);
        if (empty($result)) {
            $rootJoins = $qb->getDQLPart('join');
            foreach ($rootJoins as $joins) {
                /** @var Join[] $joins */
                foreach ($joins as $join) {
                    if ($join->getAlias() === $alias) {
                        $result = $join->getJoin();
                        break 2;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @param string                     $rootEntity
     * @param string                     $rootAlias
     * @param string                     $rootField
     * @param string                     $relAlias
     * @param string                     $relJoinType
     *
     * @return QueryBuilder
     */
    protected function createSubQueryBuilder(
        OrmFilterDatasourceAdapter $ds,
        $rootEntity,
        $rootAlias,
        $rootField,
        $relAlias,
        $relJoinType
    ) {
        QueryBuilderUtil::checkIdentifier($relAlias);
        QueryBuilderUtil::checkIdentifier($rootAlias);

        $qb = $ds->createQueryBuilder()
            ->select($rootAlias)
            ->from($rootEntity, $rootAlias);

        if ($relJoinType === Join::LEFT_JOIN) {
            $qb->leftJoin(QueryBuilderUtil::getField($rootAlias, $rootField), $relAlias);
        } else {
            $qb->innerJoin(QueryBuilderUtil::getField($rootAlias, $rootField), $relAlias);
        }

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $alias
     *
     * @return null
     */
    protected function getRootEntity(QueryBuilder $qb, $alias)
    {
        $entity      = null;
        $rootAliases = $qb->getRootAliases();
        $rootAliasesCount = count($rootAliases);
        for ($i = 0; $i < $rootAliasesCount; $i++) {
            if ($rootAliases[$i] === $alias) {
                $entity = $qb->getRootEntities()[$i];
                break;
            }
        }

        return $entity;
    }
}
