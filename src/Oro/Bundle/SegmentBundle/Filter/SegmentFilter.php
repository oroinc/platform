<?php

namespace Oro\Bundle\SegmentBundle\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;

class SegmentFilter extends EntityFilter
{
    /** @var QueryProcessor */
    protected $processor;

    /**
     * Constructor
     *
     * @param FormFactoryInterface $factory
     * @param FilterUtility        $util
     * @param QueryProcessor       $processor
     */
    public function __construct(FormFactoryInterface $factory, FilterUtility $util, QueryProcessor $processor)
    {
        parent::__construct($factory, $util);
        $this->processor = $processor;
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
            $query = $this->getDynamicSegmentRestriction($segment);
        } else {
            // @TODO process static here
        }
        $field = $this->get(FilterUtility::DATA_NAME_KEY);
        $expr  = $ds->expr()->in($field, $query);
        $this->applyFilterToClause($ds, $expr);
    }

    /**
     * Converts definition of
     *
     * @param Segment $segment
     *
     * @return \Doctrine\ORM\Query
     */
    protected function getDynamicSegmentRestriction(Segment $segment)
    {
        $qb = $this->processor->process($segment->getEntity(), $segment);

        return $qb->getQuery()->getDQL();
    }
}
