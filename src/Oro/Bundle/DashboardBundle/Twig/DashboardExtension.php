<?php

namespace Oro\Bundle\DashboardBundle\Twig;

use Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateRangeConverter;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager as QueryDesignerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DashboardExtension extends \Twig_Extension
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return FilterDateRangeConverter
     */
    protected function getDateRangeConverter()
    {
        return $this->container->get('oro_dashboard.widget_config_value.date_range.converter');
    }

    /**
     * @return QueryDesignerManager
     */
    protected function getQueryDesignerManager()
    {
        return $this->container->get('oro_query_designer.query_designer.manager');
    }

    /**
     * @return EntityProvider
     */
    protected function getEntityProvider()
    {
        return $this->container->get('oro_report.entity_provider');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_filter_date_range_view', [$this, 'getViewValue']),
            new \Twig_SimpleFunction('oro_query_filter_metadata', [$this, 'getQueryFilterMetadata']),
            new \Twig_SimpleFunction('oro_query_filter_entities', [$this, 'getQueryFilterEntities'])
        ];
    }

    /**
     * @param array $value of ['start' => \DateTime(), 'end' => \DateTime(), 'type' => One of AbstractDateFilterType]
     *
     * @return string
     */
    public function getViewValue($value)
    {
        return $this->getDateRangeConverter()->getViewValue($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_dashboard';
    }

    /**
     * @return array
     */
    public function getQueryFilterMetadata()
    {
        return $this->getQueryDesignerManager()->getMetadata('segment');
    }

    /**
     * @return array
     */
    public function getQueryFilterEntities()
    {
        return $this->getEntityProvider()->getEntities();
    }
}
