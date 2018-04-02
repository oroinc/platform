<?php

namespace Oro\Bundle\ActionBundle\Resolver;

use Oro\Bundle\EntityConfigBundle\Helper\EntityConfigHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class DestinationPageResolver
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var EntityConfigHelper */
    protected $entityConfigHelper;

    /** @var RouterInterface */
    protected $router;

    const DEFAULT_DESTINATION = '';
    const AVAILABLE_DESTINATIONS = ['view', 'name', self::DEFAULT_DESTINATION];
    const PARAM_ORIGINAL_URL = 'originalUrl';

    /**
     * @param RequestStack $requestStack
     * @param EntityConfigHelper $entityConfigHelper
     * @param RouterInterface $router
     */
    public function __construct(
        RequestStack $requestStack,
        EntityConfigHelper $entityConfigHelper,
        RouterInterface $router
    ) {
        $this->requestStack = $requestStack;
        $this->entityConfigHelper = $entityConfigHelper;
        $this->router = $router;
    }

    /**
     * @return string
     */
    public function getOriginalUrl()
    {
        return $this->requestStack->getMasterRequest()->getRequestUri();
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
