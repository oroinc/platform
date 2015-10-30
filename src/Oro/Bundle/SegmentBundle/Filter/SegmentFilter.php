<?php

namespace Oro\Bundle\SegmentBundle\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\FilterBundle\Filter\AbstractFilter;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Provider\EntityNameProvider;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\FilterBundle\Datasource\ExpressionBuilderInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmExpressionBuilder;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;

class SegmentFilter extends EntityFilter
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var ServiceLink */
    protected $dynamicSegmentQueryBuilderLink;

    /** @var ServiceLink */
    protected $staticSegmentQueryBuilderLink;

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
     * @param ServiceLink          $dynamicSegmentQueryBuilderLink
     * @param ServiceLink          $staticSegmentQueryBuilderLink
     * @param EntityNameProvider   $entityNameProvider
     * @param ConfigProvider       $entityConfigProvider
     * @param ConfigProvider       $extendConfigProvider
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        ManagerRegistry $doctrine,
        ServiceLink $dynamicSegmentQueryBuilderLink,
        ServiceLink $staticSegmentQueryBuilderLink,
        EntityNameProvider $entityNameProvider,
        ConfigProvider $entityConfigProvider,
        ConfigProvider $extendConfigProvider
    ) {
        parent::__construct($factory, $util);

        $this->doctrine                       = $doctrine;
        $this->dynamicSegmentQueryBuilderLink = $dynamicSegmentQueryBuilderLink;
        $this->staticSegmentQueryBuilderLink  = $staticSegmentQueryBuilderLink;
        $this->entityNameProvider             = $entityNameProvider;
        $this->entityConfigProvider           = $entityConfigProvider;
        $this->extendConfigProvider           = $extendConfigProvider;
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
                        'property'      => 'name',
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
        if (!(isset($data['value']) && $data['value'] instanceof Segment)) {
            return false;
        }

        if (!$this->isExpressionBuilderSupported($ds->expr())) {
            throw new \LogicException('The SegmentFilter supports ORM data source only.');
        }

        $queryBuilder = $this->getSegmentQueryBuilder($data);
        $query        = $queryBuilder->getQuery();

        /**@var OrmExpressionBuilder $expressionBuilder */
        $expressionBuilder = $ds->expr();
        $expr              = $expressionBuilder->exists($query->getDQL());

        $this->applyFilterToClause($ds, $expr);

        $params = $query->getParameters();
        /** @var Parameter $param */
        foreach ($params as $param) {
            $ds->setParameter($param->getName(), $param->getValue(), $param->getType());
        }

        return true;
    }

    /**
     * @param mixed $data
     *
     * @return QueryBuilder
     */
    protected function getSegmentQueryBuilder($data)
    {
        /** @var Segment $segment */
        $segment = $data['value'];

        /** @var QueryBuilder $queryBuilder */
        if ($this->isDynamic($segment)) {
            $queryBuilder = $this->dynamicSegmentQueryBuilderLink->getService()->getQueryBuilder($segment);
        } else {
            $queryBuilder = $this->staticSegmentQueryBuilderLink->getService()->getQueryBuilder($segment);
        }
        $field = $this->get(FilterUtility::DATA_NAME_KEY);

        $queryBuilder->andWhere($this->getIdentityFieldWithAlias($queryBuilder, $segment) . ' = ' . $field);

        return $queryBuilder;
    }

    /**
     * @param Segment $segment
     *
     * @return bool
     */
    protected function isDynamic(Segment $segment)
    {
        return $segment->getType()->getName() === SegmentType::TYPE_DYNAMIC;
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

        if ($this->isDynamic($segment)) {
            return $tableAliases[0] . '.' . $idField;
        }

        $idFieldType   = $entityMetadata->getTypeOfField($idField);
        $fieldToSelect = SegmentSnapshot::ENTITY_REF_FIELD;
        if ($idFieldType == 'integer') {
            $fieldToSelect = SegmentSnapshot::ENTITY_REF_INTEGER_FIELD;
        }

        return $tableAliases[0] . '.' . $fieldToSelect;
    }
}
