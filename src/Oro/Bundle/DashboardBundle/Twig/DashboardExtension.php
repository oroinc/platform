<?php

namespace Oro\Bundle\DashboardBundle\Twig;

use Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateRangeConverter;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager as QueryDesignerManager;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions for working with dashboard filters:
 *   - oro_filter_date_range_view
 *   - oro_query_filter_metadata
 *   - oro_query_filter_entities
 */
class DashboardExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

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
            new TwigFunction('oro_filter_date_range_view', [$this, 'getViewValue']),
            new TwigFunction('oro_query_filter_metadata', [$this, 'getQueryFilterMetadata']),
            new TwigFunction('oro_query_filter_entities', [$this, 'getQueryFilterEntities'])
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_dashboard.widget_config_value.date_range.converter' => FilterDateRangeConverter::class,
            'oro_query_designer.query_designer.manager' => QueryDesignerManager::class,
            'oro_report.entity_provider' => EntityProvider::class,
        ];
    }
}
