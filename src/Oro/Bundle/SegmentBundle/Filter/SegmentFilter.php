<?php

namespace Oro\Bundle\SegmentBundle\Filter;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmExpressionBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\AbstractFilter;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Provider\EntityNameProvider;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * The filter by entities that are included in a segment.
 */
class SegmentFilter extends EntityFilter
{
    private SegmentManager $segmentManager;
    private EntityNameProvider $entityNameProvider;
    private ConfigProvider $entityConfigProvider;
    private ConfigProvider $extendConfigProvider;

    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        ManagerRegistry $doctrine,
        SegmentManager $segmentManager,
        EntityNameProvider $entityNameProvider,
        ConfigProvider $entityConfigProvider,
        ConfigProvider $extendConfigProvider
    ) {
        parent::__construct($factory, $util, $doctrine);
        $this->segmentManager = $segmentManager;
        $this->entityNameProvider = $entityNameProvider;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->extendConfigProvider = $extendConfigProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'segment';
        AbstractFilter::init($name, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata(): array
    {
        $metadata = parent::getMetadata();

        $entityIds = [];
        $configIds = $this->entityConfigProvider->getIds();
        foreach ($configIds as $configId) {
            $className = $configId->getClassName();
            if ($this->extendConfigProvider->getConfig($className)->in(
                'state',
                [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]
            )) {
                $classMetadata = $this->doctrine->getManagerForClass($className)->getClassMetadata($className);
                $identifiers = $classMetadata->getIdentifier();
                $entityIds[$className] = array_shift($identifiers);
            }
        }

        $metadata['entity_ids'] = $entityIds;

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof OrmFilterDatasourceAdapter
            || !(isset($data['value']) && $data['value'] instanceof Segment)
        ) {
            return false;
        }

        if (!$ds->expr() instanceof OrmExpressionBuilder) {
            throw new \LogicException('The SegmentFilter supports ORM data source only.');
        }

        /** @var Segment $segment */
        $segment = $data['value'];
        $subQuery = $this->segmentManager->getFilterSubQuery($segment, $ds->getQueryBuilder());

        /**@var OrmExpressionBuilder $expressionBuilder */
        $expressionBuilder = $ds->expr();
        $expr = $expressionBuilder->in($this->getDataFieldName(), $subQuery);

        $this->applyFilterToClause($ds, $expr);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function prepareData(array $data): array
    {
        if (isset($data['value'])) {
            $data['value'] = $this->getEntity(Segment::class, $data['value'], true);
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    protected function createForm(): FormInterface
    {
        $entityName = $this->entityNameProvider->getEntityName();

        // hard coded field, do not allow to pass any option
        return $this->formFactory->create(
            $this->getFormType(),
            [],
            [
                'csrf_protection' => false,
                'field_options'   => [
                    'class'         => Segment::class,
                    'choice_label'  => 'name',
                    'required'      => true,
                    'query_builder' => function (EntityRepository $repo) use ($entityName) {
                        $qb = $repo->createQueryBuilder('s');
                        if ($entityName) {
                            $qb
                                ->where('s.entity = :entity')
                                ->setParameter('entity', $entityName);
                        }

                        return $qb;
                    }
                ]
            ]
        );
    }
}
