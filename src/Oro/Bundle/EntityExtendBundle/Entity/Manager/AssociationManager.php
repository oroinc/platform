<?php

namespace Oro\Bundle\EntityExtendBundle\Entity\Manager;

use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\DBAL\Types\Type;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\QueryBuilderHelper;
use Oro\Bundle\EntityBundle\ORM\QueryUtils;
use Oro\Bundle\EntityBundle\ORM\SqlQueryBuilder;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\SoapBundle\Event\GetListBefore;

class AssociationManager
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var EventDispatcher */
    protected $eventDispatcher;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ServiceLink */
    protected $nameFormatterLink;

    /**
     * @param ConfigManager   $configManager
     * @param EventDispatcher $eventDispatcher
     * @param DoctrineHelper  $doctrineHelper
     * @param ServiceLink     $nameFormatterLink
     */
    public function __construct(
        ConfigManager $configManager,
        EventDispatcher $eventDispatcher,
        DoctrineHelper $doctrineHelper,
        ServiceLink $nameFormatterLink
    ) {
        $this->configManager     = $configManager;
        $this->eventDispatcher   = $eventDispatcher;
        $this->doctrineHelper    = $doctrineHelper;
        $this->nameFormatterLink = $nameFormatterLink;
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
            ->get('relation');
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
     * @param string      $associationOwnerClass    The FQCN of the entity that is the owning side of the association
     * @param mixed|null  $filters                  Criteria is used to filter entities which are association owners
     *                                              e.g. ['age' => 20, ...] or \Doctrine\Common\Collections\Criteria
     * @param array|null  $joins                    Additional associations required to filter owning side entities
     * @param array       $associationTargets       The list of fields responsible to store associations
     *                                              Array format: [target_entity_class => field_name]
     * @param int         $limit                    The maximum number of items per page
     * @param int         $page                     The page number
     * @param string|null $orderBy                  The ordering expression for the result
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
        $orderBy = null
    ) {
        $em       = $this->doctrineHelper->getEntityManager($associationOwnerClass);
        $criteria = $this->doctrineHelper->normalizeCriteria($filters);

        $selectStmt = null;
        $subQueries = [];
        foreach ($associationTargets as $entityClass => $fieldName) {
            // dispatch oro_api.request.get_list.before event
            $event = new GetListBefore($criteria, $entityClass);
            $this->eventDispatcher->dispatch(GetListBefore::NAME, $event);
            $subCriteria = $event->getCriteria();

            $nameExpr = $this->getNameFormatter()->getFormattedNameDQL('target', $entityClass);
            $subQb    = $em->getRepository($associationOwnerClass)->createQueryBuilder('e')
                ->select(
                    sprintf(
                        'e.id AS emailId, target.%s AS entityId, \'%s\' AS entityClass, '
                        . ($nameExpr ?: '\'\'') . ' AS entityTitle',
                        $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass),
                        str_replace('\'', '\'\'', $entityClass)
                    )
                )
                ->innerJoin('e.' . $fieldName, 'target');
            $this->doctrineHelper->applyJoins($subQb, $joins);

            // fix of doctrine error with Same Field, Multiple Values, Criteria and QueryBuilder
            // http://www.doctrine-project.org/jira/browse/DDC-2798
            // TODO revert changes when doctrine version >= 2.5 in scope of BAP-5577
            QueryBuilderHelper::addCriteria($subQb, $subCriteria);
            // $subQb->addCriteria($criteria);

            $subQuery = $subQb->getQuery();

            $subQueries[] = QueryUtils::getExecutableSql($subQuery);

            if (empty($selectStmt)) {
                $mapping    = QueryUtils::parseQuery($subQuery)->getResultSetMapping();
                $selectStmt = sprintf(
                    'entity.%s AS id, entity.%s AS entity, entity.%s AS title',
                    QueryUtils::getColumnNameByAlias($mapping, 'entityId'),
                    QueryUtils::getColumnNameByAlias($mapping, 'entityClass'),
                    QueryUtils::getColumnNameByAlias($mapping, 'entityTitle')
                );
            }
        }

        $rsm = new ResultSetMapping();
        $rsm
            ->addScalarResult('id', 'id', Type::INTEGER)
            ->addScalarResult('entity', 'entity')
            ->addScalarResult('title', 'title');
        $qb = new SqlQueryBuilder($em, $rsm);
        $qb
            ->select($selectStmt)
            ->from('(' . implode(' UNION ALL ', $subQueries) . ')', 'entity');
        if (null !== $limit) {
            $qb->setMaxResults($limit);
            if (null !== $page) {
                $qb->setFirstResult($this->doctrineHelper->getPageOffset($page, $limit));
            }
        }
        if ($orderBy) {
            $qb->orderBy($orderBy);
        }

        return $qb;
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
     * @return DQLNameFormatter
     */
    protected function getNameFormatter()
    {
        return $this->nameFormatterLink->getService();
    }
}
