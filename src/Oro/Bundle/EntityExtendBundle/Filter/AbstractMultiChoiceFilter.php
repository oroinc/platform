<?php

namespace Oro\Bundle\EntityExtendBundle\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Datasource\ManyRelationBuilder;
use Oro\Bundle\FilterBundle\Filter\ChoiceFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

abstract class AbstractMultiChoiceFilter extends ChoiceFilter
{
    /** @var ManyRelationBuilder */
    protected $manyRelationBuilder;

    /**
     * Constructor
     *
     * @param FormFactoryInterface $factory
     * @param FilterUtility        $util
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util
    ) {
        parent::__construct($factory, $util);
    }
}
