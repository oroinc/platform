<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Oro\Bundle\EntityConfigBundle\Helper\EntityConfigHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class DestinationPageHelper
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var EntityConfigHelper */
    protected $entityConfigHelper;

    /** @var RouterInterface */
    protected $router;

    const DEFAULT_DESTINATION = 'prev';
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
     * @param string $entityClass
     * @return array
     */
    public function getAvailableDestinations($entityClass)
    {
        $availableDestinations = [];

        $availableRoutes = $this->entityConfigHelper->getRoutes($entityClass, self::AVAILABLE_DESTINATIONS);
        if (isset($availableRoutes['name'])) {
            $availableDestinations[] = 'name';
        }
        if (isset($availableRoutes['view'])) {
            $availableDestinations[] = 'view';
        }

        return $availableDestinations;
    }

    /**
     * @return string
     */
    public function getOriginalUrl()
    {
        return $this->requestStack->getMasterRequest()->getRequestUri();
    }

    /**
     * @param object $entity
     * @return array
     */
    public function getDestinationUrls($entity)
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

    /**
     * @param object $entity
     * @param string $type
     * @return string
     */
    public function getDestinationUrl($entity, $type)
    {
        $urls = $this->getDestinationUrls($entity);

        return isset($urls[$type]) ? $urls[$type] : null;
    }
}
