<?php

namespace Oro\Bundle\ActionBundle\Resolver;

use Oro\Bundle\EntityConfigBundle\Helper\EntityConfigHelper;
use Symfony\Component\Routing\RouterInterface;

/**
 * Resolves the URL redirection activity by a route name from the entity configuration
 * using the `routeName` or `routeView` parameters.
 */
class DestinationPageResolver
{
    /** @var EntityConfigHelper */
    protected $entityConfigHelper;

    /** @var RouterInterface */
    protected $router;

    const DEFAULT_DESTINATION = '';
    const AVAILABLE_DESTINATIONS = ['view', 'name', self::DEFAULT_DESTINATION];
    const PARAM_ORIGINAL_URL = 'originalUrl';

    /**
     * @param EntityConfigHelper $entityConfigHelper
     * @param RouterInterface $router
     */
    public function __construct(
        EntityConfigHelper $entityConfigHelper,
        RouterInterface $router
    ) {
        $this->entityConfigHelper = $entityConfigHelper;
        $this->router = $router;
    }

    /**
     * @param string|entity $entityClass
     * @return array
     */
    public function getAvailableDestinationsForEntity($entityClass)
    {
        $availableRoutes = array_filter(
            $this->entityConfigHelper->getRoutes($entityClass, array_filter(self::AVAILABLE_DESTINATIONS))
        );

        return array_merge([self::DEFAULT_DESTINATION], array_keys($availableRoutes));
    }

    /**
     * @param object $entity
     * @param string $destination
     * @return string
     */
    public function resolveDestinationUrl($entity, $destination)
    {
        $urls = $this->getDestinationUrls($entity);

        return isset($urls[$destination]) ? $urls[$destination] : null;
    }

    /**
     * @param object $entity
     * @return array
     */
    protected function getDestinationUrls($entity)
    {
        $urls = [];

        $availableRedirects = $this->entityConfigHelper->getRoutes($entity, self::AVAILABLE_DESTINATIONS);

        if (isset($availableRedirects['name'])) {
            $urls['name'] = $this->router->generate($availableRedirects['name']);
        }
        if (isset($availableRedirects['view']) && $entity->getId()) {
            $urls['view'] = $this->router->generate($availableRedirects['view'], ['id' => $entity->getId()]);
        }

        return $urls;
    }
}
