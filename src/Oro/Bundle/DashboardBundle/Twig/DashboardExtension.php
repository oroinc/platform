<?php

namespace Oro\Bundle\DashboardBundle\Twig;

use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Component\DependencyInjection\ServiceLink;

class DashboardExtension extends \Twig_Extension
{
    /** @var ServiceLink */
    protected $converter;

    /** @var ServiceLink */
    protected $managerLink;

    /** @var EntityProvider */
    protected $entityProvider;

    /**
     * @param ServiceLink    $converterLink
     * @param ServiceLink    $managerLink Link Used instead of manager because of performance reasons
     * @param EntityProvider $entityProvider
     */
    public function __construct(
        ServiceLink $converterLink,
        ServiceLink $managerLink,
        EntityProvider $entityProvider
    ) {
        $this->converterLink = $converterLink;
        $this->managerLink = $managerLink;
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
        return $this->converterLink->getService()->getViewValue($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_dashboard';
    }

    public function getQueryFilterMetadata()
    {
        return $this->managerLink->getService()->getMetadata('segment');
    }

    public function getQueryFilterEntities()
    {
        return $this->entityProvider->getEntities();
    }
}
