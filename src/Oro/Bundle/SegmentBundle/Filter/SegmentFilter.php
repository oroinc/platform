<?php

namespace Oro\Bundle\SegmentBundle\Filter;

use Doctrine\ORM\Query\Parameter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Filter\AbstractFilter;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Query\StaticSegmentQueryBuilder;
use Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder;

class SegmentFilter extends EntityFilter
{
    /** @var DynamicSegmentQueryBuilder */
    protected $dynamicSegmentQueryBuilder;

    /** @var StaticSegmentQueryBuilder */
    protected $staticSegmentQueryBuilder;

    /**
     * Constructor
     *
     * @param FormFactoryInterface       $factory
     * @param FilterUtility              $util
     * @param DynamicSegmentQueryBuilder $dynamicSegmentQueryBuilder
     * @param StaticSegmentQueryBuilder  $staticSegmentQueryBuilder
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        DynamicSegmentQueryBuilder $dynamicSegmentQueryBuilder,
        StaticSegmentQueryBuilder $staticSegmentQueryBuilder
    ) {
        parent::__construct($factory, $util);
        $this->dynamicSegmentQueryBuilder = $dynamicSegmentQueryBuilder;
        $this->staticSegmentQueryBuilder  = $staticSegmentQueryBuilder;
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
    public function getForm()
    {
        if (!$this->form) {
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
