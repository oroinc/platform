<?php

namespace Oro\Bundle\DashboardBundle\Twig;

use Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateRangeConverter;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;

class DashboardExtension extends \Twig_Extension
{
    /** @var FilterDateRangeConverter */
    protected $converter;

    /** @var Manager */
    protected $manager;

    /** @var EntityProvider */
    protected $entityProvider;

    /**
     * @param FilterDateRangeConverter $converter
     * @param Manager                  $manager
     * @param EntityProvider           $entityProvider
     */
    public function __construct(FilterDateRangeConverter $converter, Manager $manager, EntityProvider $entityProvider)
    {
        $this->converter = $converter;
        $this->manager = $manager;
        $this->entityProvider = $entityProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            'oro_filter_date_range_view' => new \Twig_Function_Method($this, 'getViewValue'),
            'oro_query_filter_metadata' => new \Twig_Function_Method($this, 'getQueryFilterMetadata'),
            'oro_query_filter_entities' => new \Twig_Function_Method($this, 'getQueryFilterEntities')
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

    public function getQueryFilterMetadata()
    {
        return $this->manager->getMetadata('all');
    }

    public function getQueryFilterEntities()
    {
        return $this->entityProvider->getEntities();
    }
}
