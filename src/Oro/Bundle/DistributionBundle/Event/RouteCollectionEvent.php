<?php

namespace Oro\Bundle\DistributionBundle\Event;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Represents a route collection instantly after routes are loaded.
 */
class RouteCollectionEvent extends Event
{
    public const ALL = 'oro_distribution.route_collection.all';
    public const AUTOLOAD = 'oro_distribution.route_collection.autoload';
    public const EXPOSE = 'oro_distribution.route_collection.expose';

    /**
     * @var RouteCollection
     */
    protected $collection;

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
