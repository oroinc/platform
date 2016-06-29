<?php

namespace Oro\Bundle\DashboardBundle\Twig;

use Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateRangeConverter;

class FilterDateRangeExtension extends \Twig_Extension
{
    /** @var FilterDateRangeConverter */
    protected $converter;

    /**
     * @param FilterDateRangeConverter $converter
     */
    public function __construct(FilterDateRangeConverter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            'oro_filter_date_range_view' => new \Twig_Function_Method($this, 'getViewValue')
        ];
    }

    /**
     * @param array $value of ['start' => \DateTime(), 'end' => \DateTime(), 'type' => One of AbstractDateFilterType]
     *
     * @return string
     */
    public function getViewValue($value)
    {
        return $this->converter->getViewValue($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_filter_date_range';
    }
}
