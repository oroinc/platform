<?php

namespace Oro\Bundle\SecurityBundle\Search;

use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Criteria\ExpressionBuilder;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\EventListener\SearchListener;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionDataBuilderInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;

/**
 * Applies ACL check to search index queries
 */
class AclHelper
{
    /** @var SearchMappingProvider */
    protected $mappingProvider;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var AclConditionDataBuilderInterface */
    protected $ownershipDataBuilder;

    /** @var OwnershipMetadataProviderInterface */
    protected $metadataProvider;

    public function __construct(
        SearchMappingProvider $mappingProvider,
        TokenAccessorInterface $tokenAccessor,
        AclConditionDataBuilderInterface $ownershipDataBuilder,
        OwnershipMetadataProviderInterface $metadataProvider
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->mappingProvider = $mappingProvider;
        $this->ownershipDataBuilder = $ownershipDataBuilder;
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * Applies ACL conditions to the search query
     *
     * @param Query  $query
     * @param string $permission
     *
     * @return Query
     */
    public function apply(Query $query, $permission = 'VIEW')
    {
        $querySearchAliases = $this->getSearchAliases($query);

        $allowedAliases   = [];
        $ownerExpressions = [];
        $expr             = $query->getCriteria()->expr();
        if (false !== $querySearchAliases && count($querySearchAliases) !== 0) {
            foreach ($querySearchAliases as $entityAlias) {
                $className = $this->mappingProvider->getEntityClass($entityAlias);
                if (!$className) {
                    continue;
                }

                $entityConfig = $this->mappingProvider->getEntityConfig($className);
                $condition  = $this->ownershipDataBuilder->getAclConditionData(
                    $className,
                    $entityConfig['acl_permission'] ?? $permission
                );
                $expression = $this->getExpressionByCondition($className, $entityAlias, $condition, $expr);

                if (!$expression) {
                    continue;
                }

                $allowedAliases[] = $entityAlias;
                $ownerExpressions[] = $expression;
            }
        }

        if (count($ownerExpressions) !== 0) {
            $orExpression = new CompositeExpression(CompositeExpression::TYPE_OR, $ownerExpressions);
            $query->getCriteria()->andWhere($orExpression);
        }
        $query->from($allowedAliases);

        $this->addOrganizationLimits($query, $expr);

        return $query;
    }

    /**
     * @return int|null
     */
    protected function getOrganizationId()
    {
        return $this->tokenAccessor->getOrganizationId();
    }

    /**
     * @param Query $query
     * @param ExpressionBuilder $expr
     */
    protected function addOrganizationLimits(Query $query, $expr)
    {
        $organizationId = $this->getOrganizationId();
        if ($organizationId) {
            $query->getCriteria()->andWhere(
                $expr->in('integer.organization', [$organizationId, SearchListener::EMPTY_ORGANIZATION_ID])
            );
        }
    }

    /**
     * Get search query 'from' aliases
     *
     * @param Query $query
     *
     * @return array Return search aliases from Query. In case if from part = *, return all search aliases
     */
    protected function getSearchAliases(Query $query)
    {
        $queryAliases = $query->getFrom();

        if ($queryAliases[0] === '*') {
            $queryAliases = $this->mappingProvider->getEntitiesListAliases();
        }

        return $queryAliases;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getExpressionByCondition(
        string $className,
        string $entityAlias,
        array $condition,
        ExpressionBuilder $expressionBuilder
    ): ?Expression {
        if (count($condition) === 0) {
            return $this->getNoLimitExpression($expressionBuilder, $className, $entityAlias);
        }

        // checking that owner field name and organization field name are not null or
        // checking that owner exists and  property for ignore owner is not false
        if ($condition[0] === null && $condition[2] === null
            || ($condition[0] === null && $condition[1] === null && !$condition[4])
        ) {
            return null;
        }

        if ($condition[1] === null) {
            return $this->getNoLimitExpression($expressionBuilder, $className, $entityAlias);
        }

        $filterField = $this->getFieldWithEntityAlias($entityAlias, $condition[0] ?? $this->getOwnerField($className));

        $owners = !empty($condition[1])
            ? $condition[1]
            : SearchListener::EMPTY_OWNER_ID;

        if (\is_array($owners)) {
            return \count($owners) === 1
                ? $expressionBuilder->eq('integer.' . $filterField, reset($owners))
                : $expressionBuilder->in('integer.' . $filterField, $owners);
        }

        return $expressionBuilder->eq('integer.' . $filterField, $owners);
    }

    private function getOwnerField(string $className): string
    {
        $metadata = $this->metadataProvider->getMetadata($className);

        return $metadata->getOwnerFieldName() ?? '';
    }

    private function getNoLimitExpression(
        ExpressionBuilder $expressionBuilder,
        string $className,
        string $entityAlias
    ): Expression {
        return $expressionBuilder->gte(
            'integer.' . $this->getFieldWithEntityAlias($entityAlias, $this->getOwnerField($className)),
            SearchListener::EMPTY_OWNER_ID
        );
    }

    private function getFieldWithEntityAlias(string $entityAlias, string $field): string
    {
        return sprintf('%s_%s', $entityAlias, $field);
    }
}
