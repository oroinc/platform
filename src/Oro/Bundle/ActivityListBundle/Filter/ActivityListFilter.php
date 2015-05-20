<?php

namespace Oro\Bundle\ActivityListBundle\Filter;

use LogicException;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\ActivityListBundle\Form\Type\ActivityListFilterType;
use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

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

    /**
     * @param FormFactoryInterface $factory
     * @param FilterUtility $util
     * @param ActivityListFilterHelper $activityListFilterHelper
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        ActivityListFilterHelper $activityListFilterHelper
    ) {
        parent::__construct($factory, $util);
        $this->activityListFilterHelper = $activityListFilterHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $this->activityAlias = uniqid('r');
        $this->activityListAlias = uniqid('a');

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
        if ($metadata->isIdentifierComposite) {
            throw new \LogicException('Composite identifiers are not supported.');
        }

        $activityQb = $this->createActivityQueryBuilder($em, $data, $metadata->getIdentifier()[0]);

        $activityPart = $ds->expr()->exists($activityQb->getQuery()->getDQL());
        if ($type === static::TYPE_HAS_NOT_ACTIVITY) {
            $activityPart = $ds->expr()->not($activityPart);
        }
        $this->applyFilterToClause($ds, $activityPart);

        foreach ($activityQb->getParameters() as $parameter) {
            $qb->setParameter(
                $parameter->getName(),
                $parameter->getValue(),
                $parameter->getType()
            );
        }
    }

    /**
     * @param EntityManager $activityManager
     * @param array $activityFilter
     * @param string $entityIdField
     *
     * @return QueryBuilder
     */
    protected function createActivityQueryBuilder(
        EntityManager $activityManager,
        array $activityFilter,
        $entityIdField
    ) {
        $joinField = sprintf(
            '%s.%s',
            $this->activityListAlias,
            ExtendHelper::buildAssociationName(
                $activityFilter['entityClassName'],
                ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND
            )
        );

        $activityQb = $activityManager
            ->getRepository('OroActivityListBundle:ActivityList')
            ->createQueryBuilder($this->activityListAlias);

        $activityQb
            ->select('1')
                ->join($joinField, $this->activityAlias)
                ->andWhere(sprintf('%s.id = %s.%s', $this->activityAlias, $this->getEntityAlias(), $entityIdField))
                ->setMaxResults(1);

        $entityField = $this->getEntityField();
        $dateRangeField = strpos($entityField, '$') === 0 ? substr($entityField, 1) : null;
        if ($dateRangeField) {
            $activityFilter['dateRange'] = $activityFilter['filter']['data'];
        }
        $this->activityListFilterHelper->addFiltersToQuery(
            $activityQb,
            $activityFilter,
            $dateRangeField,
            $this->activityListAlias
        );

        return $activityQb;
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

        return $field;
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
