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
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
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

    public function getQueryFilterMetadata(): array
    {
        return $this->getQueryDesignerManager()->getMetadata('segment');
    }

    public function getQueryFilterEntities(): array
    {
        return $this->getEntityProvider()->getEntities();
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_dashboard.widget_config_value.date_range.converter' => FilterDateRangeConverter::class,
            'oro_report.entity_provider' => EntityProvider::class,
            QueryDesignerManager::class
        ];
    }

    private function getDateRangeConverter(): FilterDateRangeConverter
    {
        return $this->container->get('oro_dashboard.widget_config_value.date_range.converter');
    }

    private function getEntityProvider(): EntityProvider
    {
        return $this->container->get('oro_report.entity_provider');
    }

    private function getQueryDesignerManager(): QueryDesignerManager
    {
        return $this->container->get(QueryDesignerManager::class);
    }
}
