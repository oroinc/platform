<?php

namespace Oro\Bundle\ActivityListBundle\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

use LogicException;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\ActivityListBundle\Form\Type\ActivityListFilterType;
use Oro\Bundle\ActivityListBundle\Model\ActivityListQueryDesigner;
use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;

class ActivityListFilter extends EntityFilter
{
    const TYPE_HAS_ACTIVITY = 'hasActivity';
    const TYPE_HAS_NOT_ACTIVITY = 'hasNotActivity';

    /** @var ActivityListFilterHelper */
    protected $activityListFilterHelper;

    /** @var string */
    protected $activityAlias;

    /** @var string */
    protected $activityListAlias;

    /** @var Manager */
    protected $queryDesignerManager;

    /** @var DatagridConfigurationBuilder */
    protected $datagridConfigurationBuilder;

    /** @var ServiceLink */
    protected $gridBuilderLink;

    /**
     * @param FormFactoryInterface $factory
     * @param FilterUtility $util
     * @param ActivityListFilterHelper $activityListFilterHelper
     * @param Manager $queryDesignerManager
     * @param DatagridConfigurationBuilder $datagridConfigurationBuilder
     * @param ServiceLink $gridBuilderLink
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        ActivityListFilterHelper $activityListFilterHelper,
        Manager $queryDesignerManager,
        DatagridConfigurationBuilder $datagridConfigurationBuilder,
        ServiceLink $gridBuilderLink
    ) {
        parent::__construct($factory, $util);
        $this->activityListFilterHelper = $activityListFilterHelper;
        $this->queryDesignerManager = $queryDesignerManager;
        $this->datagridConfigurationBuilder = $datagridConfigurationBuilder;
        $this->gridBuilderLink = $gridBuilderLink;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $this->activityAlias = $ds->generateParameterName('r');
        $this->activityListAlias = $ds->generateParameterName('a');

        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            throw new LogicException(sprintf(
                '"Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter" expected but "%s" given.',
                get_class($ds)
            ));
        }

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
        $joinField = sprintf(
            '%s.%s',
            $this->activityListAlias,
            ExtendHelper::buildAssociationName(
                $data['entityClassName'],
                ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND
            )
        );

        $activityQb = $em
            ->getRepository('OroActivityListBundle:ActivityList')
            ->createQueryBuilder($this->activityListAlias);

        $activityQb
            ->select('1')
                ->join($joinField, $this->activityAlias)
                ->andWhere(sprintf('%s.id = %s.%s', $this->activityAlias, $this->getEntityAlias(), $entityIdField))
                ->setMaxResults(1);

        $entityField = $this->getField();
        $dateRangeField = strpos($entityField, '$') === 0 ? substr($entityField, 1) : null;
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
        $qb = $grid->getDatasource()->getQueryBuilder();

        $alias = $qb->getRootAliases()[0];
        if ($joinPart = $qb->getDQLPart('join')) {
            $lastGroup = end($joinPart);
            $alias = end($lastGroup)->getAlias();
        }

        $field = $this->getField();
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
     * @throws LogicException
     */
    protected function getIdentifier(ClassMetadata $metadata)
    {
        if ($metadata->isIdentifierComposite) {
            throw new \LogicException('Composite identifiers are not supported.');
        }

        return $metadata->getIdentifier()[0];
    }

    /**
     * @param QueryBuilder $from
     * @param QueryBuilder $to
     */
    protected function copyParameters(QueryBuilder $from, QueryBuilder $to)
    {
        foreach ($from->getParameters() as $parameter) {
            $to->setParameter(
                $parameter->getName(),
                $parameter->getValue(),
                $parameter->getType()
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
        $source = $this->createRelatedActivitySource($data);
        $this->datagridConfigurationBuilder->setGridName('related-activity');
        $this->datagridConfigurationBuilder->setSource($source);
        $config = $this->datagridConfigurationBuilder->getConfiguration();

        return $this->gridBuilderLink->getService()->build($config, new ParameterBag());
    }

    /**
     * @param array $data
     *
     * @return ActivityListQueryDesigner
     */
    protected function createRelatedActivitySource(array $data)
    {
        $source = new ActivityListQueryDesigner();
        $source
            ->setEntity($this->getRelatedActivityClass($data))
            ->setDefinition(json_encode([
                'filters' => [
                    [
                        'columnName' => $this->getEntityField(),
                        'criterion' => $data['filter'],
                    ],
                ],
                'columns' => [
                    [
                        'name' => 'id',
                        'column' => 'id',
                        'func' => '',
                        'sort' => '',
                    ],
                ],
            ]));

        return $source;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    protected function getRelatedActivityClass(array $data)
    {
        return str_replace('_', '\\', $data['activityType']['value'][0]);
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param string $name
     * @param string $field
     * @param mixed $data
     */
    protected function applyFilter(FilterDatasourceAdapterInterface $ds, $name, $field, $data)
    {
        $filter = $this->queryDesignerManager->createFilter($name, [
            FilterUtility::DATA_NAME_KEY => $field,
        ]);

        $form = $filter->getForm();
        if (!$form->isSubmitted()) {
            $form->submit($data);
        }

        if ($form->isValid()) {
            $filter->apply($ds, $form->getData());
        }
    }

    /**
     * @return string
     */
    protected function getEntityAlias()
    {
        list($alias) = explode('.', $this->getOr(FilterUtility::DATA_NAME_KEY));

        return $alias;
    }

    /**
     * @return string
     */
    protected function getEntityField()
    {
        list(, $field) = explode('.', $this->getOr(FilterUtility::DATA_NAME_KEY));

        return base64_decode($field);
    }

    /**
     * @param string $columnName
     *
     * @return string
     */
    protected function getField()
    {
        $fieldName = $this->getEntityField();
        if (strpos($fieldName, '\\') === false) {
            return $fieldName;
        }

        $matches = [];
        preg_match('/[^:+]+$/', $fieldName, $matches);

        return $matches[0];
    }

    /**
     * {@inheritDoc}
     */
    public function getForm()
    {
        if (!$this->form) {
            $this->form = $this->formFactory->create($this->getFormType());
        }

        return $this->form;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return ActivityListFilterType::NAME;
    }
}
