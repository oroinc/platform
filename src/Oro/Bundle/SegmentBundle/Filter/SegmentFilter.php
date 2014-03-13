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
    /**
     * Constructor
     *
     * @param FormFactoryInterface $factory
     * @param FilterUtility        $util
     */
    public function __construct(FormFactoryInterface $factory, FilterUtility $util)
    {
        parent::__construct($factory, $util);
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
            $this->processDynamicSegment($ds, $segment);
        } else {
            // @TODO process static here
        }
    }

    /**
     * Converts definition of
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param Segment                          $segment
     */
    protected function processDynamicSegment(FilterDatasourceAdapterInterface $ds, Segment $segment)
    {

    }
}
