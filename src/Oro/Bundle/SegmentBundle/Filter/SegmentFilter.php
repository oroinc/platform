<?php

namespace Oro\Bundle\SegmentBundle\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\FilterBundle\Datasource\ExpressionBuilderInterface;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmExpressionBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\AbstractFilter;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Provider\EntityNameProvider;
use Symfony\Component\Form\FormFactoryInterface;

class SegmentFilter extends EntityFilter
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var SegmentManager */
    protected $segmentManager;

    /** @var EntityNameProvider */
    protected $entityNameProvider;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /**
     * Constructor
     *
     * @param FormFactoryInterface $factory
     * @param FilterUtility        $util
     * @param ManagerRegistry      $doctrine
     * @param SegmentManager       $segmentManager
     * @param EntityNameProvider   $entityNameProvider
     * @param ConfigProvider       $entityConfigProvider
     * @param ConfigProvider       $extendConfigProvider
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        ManagerRegistry $doctrine,
        SegmentManager $segmentManager,
        EntityNameProvider $entityNameProvider,
        ConfigProvider $entityConfigProvider,
        ConfigProvider $extendConfigProvider
    ) {
        parent::__construct($factory, $util);

        $this->doctrine = $doctrine;
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
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();

        $entityIds = [];
        $configIds = $this->entityConfigProvider->getIds();
        foreach ($configIds as $configId) {
            $className = $configId->getClassName();
            if ($this->extendConfigProvider->getConfig($className)->in(
                'state',
                [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]
            )
            ) {
                $classMetadata         = $this->doctrine
                    ->getManagerForClass($className)
                    ->getClassMetadata($className);
                $identifiers           = $classMetadata->getIdentifier();
                $entityIds[$className] = array_shift($identifiers);
            }
        }

        $metadata['entity_ids'] = $entityIds;

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function getForm()
    {
        if (!$this->form) {
            $entityName = $this->entityNameProvider->getEntityName();

            // hard coded field, do not allow to pass any option
            $this->form = $this->formFactory->create(
                $this->getFormType(),
                [],
                [
                    'csrf_protection' => false,
                    'field_options'   => [
                        'class'         => 'OroSegmentBundle:Segment',
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

        return $this->form;
    }

    /**
     * @param ExpressionBuilderInterface $expression
     *
     * @return bool
     */
    public function isExpressionBuilderSupported(ExpressionBuilderInterface $expression)
    {
        return $expression instanceof OrmExpressionBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof OrmFilterDatasourceAdapter
            || !(isset($data['value']) && $data['value'] instanceof Segment)
        ) {
            return false;
        }

        if (!$this->isExpressionBuilderSupported($ds->expr())) {
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
     * @param QueryBuilder $queryBuilder
     * @param Segment      $segment
     *
     * @return string
     */
    protected function getIdentityFieldWithAlias(QueryBuilder $queryBuilder, Segment $segment)
    {
        $tableAliases   = $queryBuilder->getRootAliases();
        $em             = $queryBuilder->getEntityManager();
        $entityMetadata = $em->getClassMetadata($segment->getEntity());
        $idField        = $entityMetadata->getSingleIdentifierFieldName();

        if ($segment->isDynamic()) {
            return $tableAliases[0] . '.' . $idField;
        }

        $idFieldType   = $entityMetadata->getTypeOfField($idField);
        $fieldToSelect = SegmentSnapshot::ENTITY_REF_FIELD;
        if ($idFieldType === 'integer') {
            $fieldToSelect = SegmentSnapshot::ENTITY_REF_INTEGER_FIELD;
        }

        return $tableAliases[0] . '.' . $fieldToSelect;
    }
}
