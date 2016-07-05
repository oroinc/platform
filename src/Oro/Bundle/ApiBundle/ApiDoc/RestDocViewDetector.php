<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Request\RequestType;

class RestDocViewDetector
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var string|null */
    protected $view;

    /** @var string|null */
    protected $version;

    /** @var RequestType|null */
    protected $requestType;

    /** @var RequestTypeProviderInterface[] */
    protected $requestTypeProviders = [];

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Registers a Data API request type provider to the chain.
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
        if (null === $this->view) {
            $request = $this->requestStack->getMasterRequest();
            $view = null !== $request && $request->attributes->has('view')
                ? $request->attributes->get('view')
                : '';
            $this->setView($view);
        }

        return $this->view;
    }

    /**
     * @param string|null $view
     */
    public function setView($view = null)
    {
        $this->view = $view;
        $this->requestType = null;
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
