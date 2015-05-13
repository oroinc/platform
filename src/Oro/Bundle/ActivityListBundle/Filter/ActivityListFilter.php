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
        $metadata = $em->getClassMetadata($this->getClassName());
        if ($metadata->isIdentifierComposite) {
            throw new \LogicException('Composite identifiers are not supported.');
        }

        $activityQb = $this->createActivityQueryBuilder($em, $data, $metadata->getIdentifier()[0]);

        $activityPart = $activityQb->expr()->exists($activityQb->getQuery()->getDQL());
        if ($type === static::TYPE_HAS_NOT_ACTIVITY) {
            $activityPart = $activityQb->expr()->not($activityPart);
        }
        $qb->andWhere($activityPart);

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
        $joinField = 'activity.' . ExtendHelper::buildAssociationName(
            $this->getClassName(),
            ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND
        );

        $activityQb = $activityManager
            ->getRepository('OroActivityListBundle:ActivityList')
            ->createQueryBuilder('activity');

        $activityQb
            ->select('1')
                ->join($joinField, 'r')
                ->andWhere(sprintf('r.id = %s.%s', $this->getEntityAlias(), $entityIdField))
                ->setMaxResults(1);

        $this->activityListFilterHelper->addFiltersToQuery($activityQb, $activityFilter);

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
    protected function getClassName()
    {
        list(, $className) = explode('.', $this->getOr(FilterUtility::DATA_NAME_KEY));

        return $className;
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
