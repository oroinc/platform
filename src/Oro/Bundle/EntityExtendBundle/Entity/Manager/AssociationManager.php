<?php

namespace Oro\Bundle\EntityExtendBundle\Entity\Manager;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\SqlQueryBuilder;
use Oro\Bundle\EntityBundle\ORM\UnionQueryBuilder;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Provides a set of methods to manage extended associations (see "Resources/doc/associations.md").
 */
class AssociationManager
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ServiceLink */
    protected $aclHelperLink;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var FeatureChecker */
    protected $featureChecker;

    /**
     * @param ConfigManager      $configManager
     * @param ServiceLink        $aclHelperLink
     * @param DoctrineHelper     $doctrineHelper
     * @param EntityNameResolver $entityNameResolver
     * @param FeatureChecker     $featureChecker
     */
    public function __construct(
        ConfigManager $configManager,
        ServiceLink $aclHelperLink,
        DoctrineHelper $doctrineHelper,
        EntityNameResolver $entityNameResolver,
        FeatureChecker $featureChecker
    ) {
        $this->configManager      = $configManager;
        $this->aclHelperLink      = $aclHelperLink;
        $this->doctrineHelper     = $doctrineHelper;
        $this->entityNameResolver = $entityNameResolver;
        $this->featureChecker     = $featureChecker;
    }

    /**
     * Returns the list of fields responsible to store associations for the given entity type
     *
     * @param string        $associationOwnerClass The FQCN of the entity that is the owning side of the association
     * @param callable|null $filter                The callback that can be used to filter returned associations.
     *                                             For example you can use it to filter active associations only.
     *                                             Signature:
     *                                             function ($ownerClass, $targetClass, ConfigManager $configManager)
     *                                             The filter should return TRUE if an association between
     *                                             $ownerClass and $targetClass is allowed.
     * @param string        $associationType       The type of the association.
     *                                             For example manyToOne or manyToMany
     *                                             {@see Oro\Bundle\EntityExtendBundle\Extend\RelationType}
     * @param string        $associationKind       The kind of the association.
     *                                             For example 'activity', 'sponsorship' etc
     *                                             Can be NULL for unclassified (default) association
     *
     * @return array [target_entity_class => field_name]
     */
    public function getAssociationTargets(
        $associationOwnerClass,
        $filter,
        $associationType,
        $associationKind = null
    ) {
        $result = [];

        $relations = $this->configManager->getProvider('extend')
            ->getConfig($associationOwnerClass)
            ->get('relation', false, []);
        foreach ($relations as $relation) {
            if ($this->isSupportedRelation($relation, $associationType, $associationKind)) {
                $targetClass = $relation['target_entity'];

                if (null === $filter
                    || call_user_func($filter, $associationOwnerClass, $targetClass, $this->configManager)
                ) {
                    /** @var FieldConfigId $fieldConfigId */
                    $fieldConfigId = $relation['field_id'];

                    $result[$targetClass] = $fieldConfigId->getFieldName();
                }
            }
        }

        return $result;
    }

    /**
     * Returns a function which can be used to filter enabled single owner associations
     *
     * @param string $scope     The name of the entity config scope where the association is declared
     * @param string $attribute The name of the entity config attribute which indicates
     *                          whether the association is enabled or not
     *
     * @return callable
     */
    public function getSingleOwnerFilter($scope, $attribute = 'enabled')
    {
        return function ($ownerClass, $targetClass, ConfigManager $configManager) use ($scope, $attribute) {
            if (!$this->featureChecker->isResourceEnabled($targetClass, 'entities')) {
                return false;
            }

            return $configManager->getProvider($scope)
                ->getConfig($targetClass)
                ->is($attribute);
        };
    }

    /**
     * Returns a function which can be used to filter enabled multi owner associations
     *
     * @param string $scope     The name of the entity config scope where the association is declared
     * @param string $attribute The name of the entity config attribute which is used to store
     *                          enabled associations
     *
     * @return callable
     */
    public function getMultiOwnerFilter($scope, $attribute)
    {
        return function ($ownerClass, $targetClass, ConfigManager $configManager) use ($scope, $attribute) {
            if (!$this->featureChecker->isResourceEnabled($targetClass, 'entities')) {
                return false;
            }

            $ownerClassNames = $configManager->getProvider($scope)
                ->getConfig($targetClass)
                ->get($attribute, false, []);

            return in_array($ownerClass, $ownerClassNames, true);
        };
    }

    /**
     * Returns a query builder that could be used for fetching the list of entities
     * associated with $associationOwnerClass entities found by $filters and $joins
     *
     * The resulting query would be something like this:
     * <code>
     * SELECT entity.id AS ownerId, entity.entityId AS id, entity.entityClass AS entity, entity.entityTitle AS title
     * FROM (
     *      SELECT [DISTINCT]
     *          e.id AS id,
     *          target.id AS entityId,
     *          {first_target_entity_class} AS entityClass,
     *          {first_target_title} AS entityTitle
     *      FROM {associationOwnerClass} AS e
     *          INNER JOIN e.{first_target_field_name} AS target
     *          {joins}
     *      WHERE {filters}
     *      UNION ALL
     *      SELECT [DISTINCT]
     *          e.id AS id,
     *          target.id AS entityId,
     *          {second_target_entity_class} AS entityClass,
     *          {second_target_title} AS entityTitle
     *      FROM {associationOwnerClass} AS e
     *          INNER JOIN e.{second_target_field_name} AS target
     *          {joins}
     *      WHERE {filters}
     *      UNION ALL
     *      ... select statements for other targets
     * ) entity
     * ORDER BY {orderBy}
     * LIMIT {limit} OFFSET {(page - 1) * limit}
     * </code>
     *
     * @param string        $associationOwnerClass The FQCN of the entity that is the owning side of the association
     * @param mixed|null    $filters               Criteria is used to filter entities which are association owners
     *                                             e.g. ['age' => 20, ...] or \Doctrine\Common\Collections\Criteria
     * @param array|null    $joins                 Additional associations required to filter owning side entities
     * @param array         $associationTargets    The list of fields responsible to store associations
     *                                             Array format: [target_entity_class => field_name]
     * @param int|null      $limit                 The maximum number of items per page
     * @param int|null      $page                  The page number
     * @param string|null   $orderBy               The ordering expression for the result
     * @param callable|null $callback              A callback function which can be used to modify child queries
     *                                             function (QueryBuilder $qb, $targetEntityClass)
     *
     * @return SqlQueryBuilder
     */
    public function getMultiAssociationsQueryBuilder(
        $associationOwnerClass,
        $filters,
        $joins,
        $associationTargets,
        $limit = null,
        $page = null,
        $orderBy = null,
        $callback = null
    ) {
        $criteria = QueryBuilderUtil::normalizeCriteria($filters);

        $qb = $this->getUnionQueryBuilder($associationOwnerClass)
            ->addSelect('id', 'ownerId', Type::INTEGER)
            ->addSelect('entityId', 'id', Type::INTEGER)
            ->addSelect('entityClass', 'entity')
            ->addSelect('entityTitle', 'title');
        foreach ($associationTargets as $entityClass => $fieldName) {
            $subQb = $this->getAssociationSubQueryBuilder($associationOwnerClass, $entityClass, $fieldName);
            QueryBuilderUtil::applyJoins($subQb, $joins);
            $subQb->addCriteria($criteria);
            if (null !== $callback && is_callable($callback)) {
                call_user_func($callback, $subQb, $entityClass);
            }
            $qb->addSubQuery($this->getAclHelper()->apply($subQb));
        }
        $this->setPaging($qb, $limit, $page);
        $this->setSorting($qb, $orderBy);

        return $qb->getQueryBuilder();
    }

    /**
     * Returns a query builder that could be used for fetching the list of entities
     * of the given type associated with $associationOwnerClass entities
     *
     * The resulting query would be something like this:
     * <code>
     *  SELECT
     *      e.id AS id,
     *      target.id AS entityId,
     *      {target_entity_class} AS entityClass,
     *      {target_title} AS entityTitle
     *  FROM {associationOwnerClass} AS e
     *      INNER JOIN e.{target_field_name} AS target
     * </code>
     *
     * @param string $associationOwnerClass
     * @param string $targetEntityClass
     * @param string $targetFieldName
     *
     * @return QueryBuilder
     */
    public function getAssociationSubQueryBuilder(
        $associationOwnerClass,
        $targetEntityClass,
        $targetFieldName
    ) {
        $targetAlias = 'target';
        $qb = $this->doctrineHelper->getEntityManagerForClass($associationOwnerClass)
            ->getRepository($associationOwnerClass)
            ->createQueryBuilder('e');

        return $qb->select(
            'e.id AS id',
            sprintf(
                'target.%s AS entityId',
                $this->doctrineHelper->getSingleEntityIdentifierFieldName($targetEntityClass)
            ),
            (string)$qb->expr()->literal($targetEntityClass) . ' AS entityClass',
            $this->entityNameResolver->prepareNameDQL(
                $this->entityNameResolver->getNameDQL($targetEntityClass, $targetAlias),
                true
            ) . '  AS entityTitle'
        )
        ->innerJoin(QueryBuilderUtil::getField('e', $targetFieldName), $targetAlias);
    }

    /**
     * Returns a query builder that could be used for fetching the list of owner side entities
     * the specified $associationTargetClass associated with.
     * The $filters and $joins could be used to filter entities
     *
     * The resulting query would be something like this:
     * <code>
     * SELECT entity.entityId AS id, entity.entityClass AS entity, entity.entityTitle AS title FROM (
     *      SELECT [DISTINCT]
     *          target.id AS id,
     *          e.id AS entityId,
     *          {first_owner_entity_class} AS entityClass,
     *          {first_owner_title} AS entityTitle
     *      FROM {first_owner_entity_class} AS e
     *          INNER JOIN e.{target_field_name_for_first_owner} AS target
     *          {joins}
     *      WHERE {filters}
     *      UNION ALL
     *      SELECT [DISTINCT]
     *          target.id AS id,
     *          e.id AS entityId,
     *          {second_owner_entity_class} AS entityClass,
     *          {second_owner_title} AS entityTitle
     *      FROM {second_owner_entity_class} AS e
     *          INNER JOIN e.{target_field_name_for_second_owner} AS target
     *          {joins}
     *      WHERE {filters}
     *      UNION ALL
     *      ... select statements for other owners
     * ) entity
     * ORDER BY {orderBy}
     * LIMIT {limit} OFFSET {(page - 1) * limit}
     * </code>
     *
     * @param string        $associationTargetClass The FQCN of the entity that is the target side of the association
     * @param mixed|null    $filters                Criteria is used to filter entities which are association owners
     *                                              e.g. ['age' => 20, ...] or \Doctrine\Common\Collections\Criteria
     * @param array|null    $joins                  Additional associations required to filter owning side entities
     * @param array         $associationOwners      The list of fields responsible to store associations between
     *                                              the given target and association owners
     *                                              Array format: [owner_entity_class => field_name]
     * @param int|null      $limit                  The maximum number of items per page
     * @param int|null      $page                   The page number
     * @param string|null   $orderBy                The ordering expression for the result
     * @param callable|null $callback               A callback function which can be used to modify child queries
     *                                              function (QueryBuilder $qb, $ownerEntityClass)
     *
     * @return SqlQueryBuilder
     */
    public function getMultiAssociationOwnersQueryBuilder(
        $associationTargetClass,
        $filters,
        $joins,
        $associationOwners,
        $limit = null,
        $page = null,
        $orderBy = null,
        $callback = null
    ) {
        $criteria = QueryBuilderUtil::normalizeCriteria($filters);

        $qb = $this->getUnionQueryBuilder($associationTargetClass)
            ->addSelect('entityId', 'id', Type::INTEGER)
            ->addSelect('entityClass', 'entity')
            ->addSelect('entityTitle', 'title');
        $targetIdFieldName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($associationTargetClass);
        foreach ($associationOwners as $ownerClass => $fieldName) {
            $subQb = $this->getAssociationOwnersSubQueryBuilder($ownerClass, $fieldName, $targetIdFieldName);
            QueryBuilderUtil::applyJoins($subQb, $joins);
            $subQb->addCriteria($criteria);
            if (null !== $callback && is_callable($callback)) {
                call_user_func($callback, $subQb, $ownerClass);
            }
            $qb->addSubQuery($this->getAclHelper()->apply($subQb));
        }
        $this->setPaging($qb, $limit, $page);
        $this->setSorting($qb, $orderBy);

        return $qb->getQueryBuilder();
    }

    /**
     * Returns a query builder that could be used for fetching the list of owner side entities
     * the specified $associationTargetClass associated with.
     *
     * The resulting query would be something like this:
     * <code>
     * SELECT
     *     target.id AS id,
     *     e.id AS entityId,
     *     {owner_entity_class} AS entityClass,
     *     {owner_title} AS entityTitle
     * FROM {first_owner_entity_class} AS e
     *     INNER JOIN e.{target_field_name_for_owner} AS target
     * </code>
     *
     * @param string $associationOwnerClass
     * @param string $ownerFieldName
     * @param string $targetIdFieldName
     *
     * @return QueryBuilder
     */
    public function getAssociationOwnersSubQueryBuilder(
        $associationOwnerClass,
        $ownerFieldName,
        $targetIdFieldName
    ) {
        $qb = $this->doctrineHelper->getEntityManagerForClass($associationOwnerClass)
            ->getRepository($associationOwnerClass)
            ->createQueryBuilder('e');

        return $qb
            ->select(
                QueryBuilderUtil::sprintf('target.%s AS id', $targetIdFieldName),
                'e.id AS entityId',
                (string)$qb->expr()->literal($associationOwnerClass) . ' AS entityClass',
                $this->entityNameResolver->prepareNameDQL(
                    $this->entityNameResolver->getNameDQL($associationOwnerClass, 'e'),
                    true
                ) . ' AS entityTitle'
            )
            ->innerJoin(QueryBuilderUtil::getField('e', $ownerFieldName), 'target');
    }

    /**
     * @param string $entityClass
     *
     * @return UnionQueryBuilder
     */
    private function getUnionQueryBuilder($entityClass)
    {
        return new UnionQueryBuilder($this->doctrineHelper->getEntityManagerForClass($entityClass));
    }

    /**
     * @param UnionQueryBuilder $qb
     * @param int|null          $limit
     * @param int|null          $page
     */
    private function setPaging(UnionQueryBuilder $qb, $limit = null, $page = null)
    {
        if (null !== $limit) {
            $qb->setMaxResults($limit);
            if (null !== $page) {
                $qb->setFirstResult(QueryBuilderUtil::getPageOffset($page, $limit));
            }
        }
    }

    /**
     * @param UnionQueryBuilder $qb
     * @param string|null       $orderBy
     */
    private function setSorting(UnionQueryBuilder $qb, $orderBy = null)
    {
        if ($orderBy) {
            $qb->addOrderBy($orderBy);
        }
    }

    /**
     * @param array  $relation
     * @param string $associationType
     * @param string $associationKind
     *
     * @return bool
     */
    protected function isSupportedRelation($relation, $associationType, $associationKind)
    {
        /** @var FieldConfigId|null $fieldConfigId */
        $fieldConfigId = $relation['field_id'];

        return
            $fieldConfigId instanceof FieldConfigId
            && $relation['owner']
            && (
                $fieldConfigId->getFieldType() === $associationType
                || (
                    $associationType === RelationType::MULTIPLE_MANY_TO_ONE
                    && $fieldConfigId->getFieldType() === RelationType::MANY_TO_ONE
                )
            )
            && $fieldConfigId->getFieldName() === ExtendHelper::buildAssociationName(
                $relation['target_entity'],
                $associationKind
            );
    }

    /**
     * @return AclHelper
     */
    protected function getAclHelper()
    {
        return $this->aclHelperLink->getService();
    }
}
