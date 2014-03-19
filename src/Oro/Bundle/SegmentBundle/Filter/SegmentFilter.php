<?php

namespace Oro\Bundle\SegmentBundle\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Parameter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FilterBundle\Filter\AbstractFilter;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Query\StaticSegmentQueryBuilder;
use Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder;
use Oro\Bundle\SegmentBundle\Provider\EntityNameProvider;

class SegmentFilter extends EntityFilter
{
    /** @var DynamicSegmentQueryBuilder */
    protected $dynamicSegmentQueryBuilder;

    /** @var StaticSegmentQueryBuilder */
    protected $staticSegmentQueryBuilder;

    /** @var EntityNameProvider */
    protected $entityNameProvider;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var EntityManager */
    protected $em;

    /**
     * Constructor
     *
     * @param FormFactoryInterface        $factory
     * @param FilterUtility               $util
     * @param DynamicSegmentQueryBuilder  $dynamicSegmentQueryBuilder
     * @param StaticSegmentQueryBuilder   $staticSegmentQueryBuilder
     * @param EntityNameProvider          $entityNameProvider
     * @param ConfigProvider              $configProvider
     * @param EntityManager               $em
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        DynamicSegmentQueryBuilder $dynamicSegmentQueryBuilder,
        StaticSegmentQueryBuilder $staticSegmentQueryBuilder,
        EntityNameProvider $entityNameProvider,
        ConfigProvider $configProvider,
        EntityManager $em
    ) {
        parent::__construct($factory, $util);

        $this->dynamicSegmentQueryBuilder = $dynamicSegmentQueryBuilder;
        $this->staticSegmentQueryBuilder  = $staticSegmentQueryBuilder;
        $this->entityNameProvider         = $entityNameProvider;
        $this->configProvider             = $configProvider;
        $this->em                         = $em;
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

        $em = $this->em;
        $configs = $this->configProvider->getConfigs();
        $configs = array_map(
            function (Config $item) {
                return $item->getId()->getClassName();
            },
            $configs
        );

        $result = [];
        foreach ($configs as $entityName) {
            $classMetadata = $em->getClassMetadata($entityName);
            $identifiers   = $classMetadata->getIdentifier();
            $result[$entityName] = array_shift($identifiers);
        }

        $metadata['entity_ids'] = $result;

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
                        'class'    => 'OroSegmentBundle:Segment',
                        'property' => 'name',
                        'required' => true,
                        'query_builder' => function (EntityRepository $repo) use ($entityName) {
                            return $repo->createQueryBuilder('s')
                                ->where('s.entity = :entity')
                                ->setParameter('entity', $entityName);
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
        if ($segment->getType()->getName() === SegmentType::TYPE_DYNAMIC) {
            $query = $this->dynamicSegmentQueryBuilder->build($segment);
        } else {
            $query = $this->staticSegmentQueryBuilder->build($segment);
        }
        $field = $this->get(FilterUtility::DATA_NAME_KEY);
        $expr  = $ds->expr()->in($field, $query->getDQL());
        $this->applyFilterToClause($ds, $expr);

        $params = $query->getParameters();
        /** @var Parameter $param */
        foreach ($params as $param) {
            $ds->setParameter($param->getName(), $param->getValue(), $param->getType());
        }
    }
}
