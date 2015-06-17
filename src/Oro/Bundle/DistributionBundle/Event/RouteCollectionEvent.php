<?php

namespace Oro\Bundle\DistributionBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Routing\RouteCollection;

class RouteCollectionEvent extends Event
{
    const AUTOLOAD = 'oro_distribution.route_collection.autoload';
    const EXPOSE = 'oro_distribution.route_collection.expose';

    /**
     * @var RouteCollection
     */
    protected $collection;

    /**
     * @param RouteCollection $collection
     */
    public function __construct(RouteCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @return RouteCollection
     */
    public function getCollection()
    {
        return $this->collection;
    }
}
