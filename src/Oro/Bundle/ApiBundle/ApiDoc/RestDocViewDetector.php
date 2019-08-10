<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The class that helps get the name of the current ApiDoc view, API version and the request type.
 */
class RestDocViewDetector
{
    /** @var RequestStack */
    private $requestStack;

    /** @var string|null */
    private $view;

    /** @var string|null */
    private $version;

    /** @var RequestType|null */
    private $requestType;

    /** @var RequestTypeProviderInterface[] */
    private $requestTypeProviders = [];

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Registers a API request type provider to the chain.
     *
     * @param RequestTypeProviderInterface $requestTypeProvider
     */
    public function addRequestTypeProvider(RequestTypeProviderInterface $requestTypeProvider)
    {
        if ($requestTypeProvider instanceof RestDocViewDetectorAwareInterface) {
            $requestTypeProvider->setRestDocViewDetector($this);
        }
        $this->requestTypeProviders[] = $requestTypeProvider;
    }

    /**
     * @return string
     */
    public function getView()
    {
        $view = $this->view;
        if (null === $view) {
            $view = '';
            $request = $this->requestStack->getMasterRequest();
            if (null !== $request) {
                if ($request->attributes->has('view')) {
                    $view = $request->attributes->get('view');
                }
                $this->setView($view);
            }
        }

        return $view;
    }

    /**
     * @param string|null $view
     */
    public function setView($view = null)
    {
        $this->view = $view;
        $this->requestType = null;
        $this->version = null;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        if (null === $this->version) {
            $this->setVersion(Version::normalizeVersion(null));
        }

        return $this->version;
    }

    /**
     * @param string|null $version
     */
    public function setVersion($version = null)
    {
        $this->version = $version;
    }

    /**
     * @return RequestType
     */
    public function getRequestType()
    {
        if (null === $this->requestType) {
            foreach ($this->requestTypeProviders as $provider) {
                $this->requestType = $provider->getRequestType();
                if (null !== $this->requestType) {
                    break;
                }
            }
            if (null === $this->requestType) {
                $this->requestType = new RequestType([]);
            }
        }

        return $this->requestType;
    }
}
