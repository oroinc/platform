<?php

namespace Oro\Bundle\SegmentBundle\Filter;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parameter;

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

class SegmentFilter extends EntityFilter
{
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
     * @param ServiceLink          $dynamicSegmentQueryBuilderLink
     * @param ServiceLink          $staticSegmentQueryBuilderLink
     * @param EntityNameProvider   $entityNameProvider
     * @param ConfigProvider       $entityConfigProvider
     * @param ConfigProvider       $extendConfigProvider
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        ServiceLink $dynamicSegmentQueryBuilderLink,
        ServiceLink $staticSegmentQueryBuilderLink,
        EntityNameProvider $entityNameProvider,
        ConfigProvider $entityConfigProvider,
        ConfigProvider $extendConfigProvider
    ) {
        parent::__construct($factory, $util);

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
        $em        = $this->entityConfigProvider->getConfigManager()->getEntityManager();
        $configIds = $this->entityConfigProvider->getIds();
        foreach ($configIds as $configId) {
            $className = $configId->getClassName();
            if ($this->extendConfigProvider->getConfig($className)->in(
                'state',
                [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]
            )
            ) {
                $classMetadata         = $em->getClassMetadata($className);
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
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!(isset($data['value']) && $data['value'] instanceof Segment)) {
            return false;
        }

        /** @var Segment $segment */
        $segment = $data['value'];
        /** @var Query $query */
        if ($segment->getType()->getName() === SegmentType::TYPE_DYNAMIC) {
            $query = $this->dynamicSegmentQueryBuilderLink->getService()->build($segment);
        } else {
            $query = $this->staticSegmentQueryBuilderLink->getService()->build($segment);
        }
        $field = $this->get(FilterUtility::DATA_NAME_KEY);
        $expr  = $ds->expr()->in($field, $query->getDQL());
        $this->applyFilterToClause($ds, $expr);

        $params = $query->getParameters();
        /** @var Parameter $param */
        foreach ($params as $param) {
            $ds->setParameter($param->getName(), $param->getValue(), $param->getType());
        }

        return true;
    }
}
