<?php

namespace Oro\Bundle\ActivityListBundle\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityBundle\Tools\ActivityAssociationHelper;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Form\Type\ActivityListFilterType;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterExecutionContext;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager as QueryDesignerManager;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * The filter by an activity list.
 */
class ActivityListFilter extends EntityFilter
{
    const TYPE_HAS_ACTIVITY = 'hasActivity';
    const TYPE_HAS_NOT_ACTIVITY = 'hasNotActivity';

    /** @var FilterExecutionContext */
    protected $filterExecutionContext;

    /** @var ActivityListFilterHelper */
    protected $activityListFilterHelper;

    /** @var ActivityAssociationHelper */
    protected $activityAssociationHelper;

    /** @var ActivityListChainProvider */
    protected $activityListChainProvider;

    /** @var string */
    protected $activityAlias;

    /** @var string */
    protected $activityListAlias;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /** @var QueryDesignerManager */
    protected $queryDesignerManager;

    /** @var RelatedActivityDatagridFactory */
    protected $relatedActivityDatagridFactory;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        ManagerRegistry $doctrine,
        FilterExecutionContext $filterExecutionContext,
        ActivityAssociationHelper $activityAssociationHelper,
        ActivityListChainProvider $activityListChainProvider,
        ActivityListFilterHelper $activityListFilterHelper,
        EntityRoutingHelper $entityRoutingHelper,
        QueryDesignerManager $queryDesignerManager,
        RelatedActivityDatagridFactory $relatedActivityDatagridFactory
    ) {
        parent::__construct($factory, $util, $doctrine);
        $this->filterExecutionContext = $filterExecutionContext;
        $this->activityAssociationHelper = $activityAssociationHelper;
        $this->activityListChainProvider = $activityListChainProvider;
        $this->activityListFilterHelper = $activityListFilterHelper;
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->queryDesignerManager = $queryDesignerManager;
        $this->relatedActivityDatagridFactory = $relatedActivityDatagridFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            throw new UnexpectedTypeException($ds, OrmFilterDatasourceAdapter::class);
        }

        $this->activityAlias = $ds->generateParameterName('r');
        $this->activityListAlias = $ds->generateParameterName('a');

        $type = $data['filterType'];
        unset($data['filterType']);

        $qb = $ds->getQueryBuilder();

        $em = $qb->getEntityManager();
        $metadata = $em->getClassMetadata($data['entityClassName']);
        $activityQb = $this->createActivityQueryBuilder($em, $data, $this->getIdentifier($metadata));

        $activityPart = $ds->expr()->exists($activityQb->getQuery()->getDQL());
        if ($type === static::TYPE_HAS_NOT_ACTIVITY) {
            $activityPart = $ds->expr()->not($activityPart);
        }
        $this->applyFilterToClause($ds, $activityPart);

        $this->copyParameters($activityQb, $qb);
    }

    /**
     * @param EntityManager $em
     * @param array $data
     * @param string $entityIdField
     *
     * @return QueryBuilder
     */
    protected function createActivityQueryBuilder(
        EntityManager $em,
        array $data,
        $entityIdField
    ) {
        QueryBuilderUtil::checkIdentifier($entityIdField);
        $entityClass = $data['entityClassName'];

        $joinField = sprintf(
            '%s.%s',
            $this->activityListAlias,
            ExtendHelper::buildAssociationName($entityClass, ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND)
        );

        $activityListRepository = $em->getRepository(ActivityList::class);

        $activityQb = $activityListRepository
            ->createQueryBuilder($this->activityListAlias)
            ->select('1')
            ->setMaxResults(1);

        if (!$this->activityAssociationHelper->hasActivityAssociations($entityClass)
            && !$activityListRepository->getRecordsCountForTargetClass($entityClass)
        ) {
            $activityQb->andWhere('1 = 0');

            return $activityQb;
        }

        $activityQb
            ->join($joinField, $this->activityAlias)
            ->andWhere(sprintf('%s.id = %s.%s', $this->activityAlias, $this->getEntityAlias(), $entityIdField));

        $entityField = $this->getField($data);
        $dateRangeField = str_starts_with($entityField, '$') ? substr($entityField, 1) : null;
        if ($dateRangeField) {
            $data['dateRange'] = $data['filter']['data'];
            unset($data['filter']);
        }

        $this->activityListFilterHelper->addFiltersToQuery(
            $activityQb,
            $data,
            $dateRangeField,
            $this->activityListAlias
        );

        if (isset($data['filter'])) {
            $activityDs = new OrmFilterDatasourceAdapter($activityQb);
            $expr = $activityDs->expr()->exists($this->createRelatedActivityDql($activityDs, $data));
            $this->applyFilterToClause($activityDs, $expr);
        }

        return $activityQb;
    }

    /**
     * @param OrmFilterDatasourceAdapter $activityDs
     * @param array $data
     *
     * @return string
     */
    protected function createRelatedActivityDql(OrmFilterDatasourceAdapter $activityDs, array $data)
    {
        $grid = $this->createRelatedActivityGrid($data);
        /** @var QueryBuilder $qb */
        $qb = $grid->getDatasource()->getQueryBuilder();

        $alias = $qb->getRootAliases()[0];
        $joinPart = $qb->getDQLPart('join');
        if ($joinPart) {
            $lastGroup = end($joinPart);
            $alias = end($lastGroup)->getAlias();
        }

        $field = $this->getField($data);
        $ds = new OrmFilterDatasourceAdapter($grid->getDatasource()->getQueryBuilder());
        $this->applyFilter(
            $ds,
            $data['filter']['filter'],
            sprintf('%s.%s', $alias, $field),
            $data['filter']['data']
        );

        $qb->select('1');
        $this->copyParameters($qb, $activityDs->getQueryBuilder());

        $metadata = $qb->getEntityManager()->getClassMetadata($this->getRelatedActivityClass($data));
        $dql = $qb->getDQL();
        if ($qb->getRootAliases()[0] !== $alias) {
            $dql = str_replace($alias, $ds->generateParameterName('raj'), $dql);
        }
        $dql .= sprintf(
            ' AND %s.%s = %s.relatedActivityId',
            $qb->getRootAliases()[0],
            $this->getIdentifier($metadata),
            $this->activityListAlias
        );

        return str_replace($qb->getRootAliases()[0], $ds->generateParameterName('ra'), $dql);
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     * @throws \LogicException
     */
    protected function getIdentifier(ClassMetadata $metadata)
    {
        if ($metadata->isIdentifierComposite) {
            throw new \LogicException('Composite identifiers are not supported.');
        }

        return $metadata->getIdentifier()[0];
    }

    protected function copyParameters(QueryBuilder $from, QueryBuilder $to)
    {
        foreach ($from->getParameters() as $parameter) {
            $to->setParameter(
                $parameter->getName(),
                $parameter->getValue(),
                $parameter->typeWasSpecified() ? $parameter->getType() : null
            );
        }
    }

    /**
     * @param array $data
     *
     * @return DatagridInterface
     */
    protected function createRelatedActivityGrid(array $data)
    {
        return $this->relatedActivityDatagridFactory->createGrid(new QueryDesigner(
            $this->getRelatedActivityClass($data),
            QueryDefinitionUtil::encodeDefinition([
                'filters' => [
                    ['criterion' => $data['filter']]
                ],
                'columns' => [
                    ['name' => 'id', 'column' => 'id', 'func' => '', 'sort' => '']
                ]
            ])
        ));
    }

    /**
     * @param array $data
     *
     * @return string
     */
    protected function getRelatedActivityClass(array $data)
    {
        return $this->entityRoutingHelper->resolveEntityClass($data['activityType']['value'][0]);
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param string $name
     * @param string $field
     * @param mixed $data
     */
    protected function applyFilter(FilterDatasourceAdapterInterface $ds, $name, $field, $data)
    {
        $filter = $this->queryDesignerManager->createFilter(
            $name,
            [FilterUtility::DATA_NAME_KEY => $field]
        );

        $normalizedData = $this->filterExecutionContext->normalizedFilterData($filter, $data);
        if (null !== $normalizedData) {
            $filter->apply($ds, $normalizedData);
        }
    }

    /**
     * @return string
     */
    protected function getEntityAlias()
    {
        [$alias] = explode('.', $this->getOr(FilterUtility::DATA_NAME_KEY));
        QueryBuilderUtil::checkIdentifier($alias);

        return $alias;
    }

    /**
     * @return string
     */
    protected function getEntityField(array $data)
    {
        return $data['activityFieldName'];
    }

    /**
     * @return string
     */
    protected function getField(array $data)
    {
        $fieldName = $this->getEntityField($data);
        if (!str_contains($fieldName, '\\')) {
            return $fieldName;
        }

        $matches = [];
        preg_match('/[^:+]+$/', $fieldName, $matches);

        return $matches[0];
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType()
    {
        return ActivityListFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function createForm(): FormInterface
    {
        return $this->formFactory->create($this->getFormType());
    }
}
