<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The class that helps get the name of the current ApiDoc view, API version and the request type.
 */
class RestDocViewDetector implements ResetInterface
{
    /** @var RequestStack */
    private $requestStack;

    /** @var string|null */
    private $view;

    /** @var string|null */
    private $version;

    /** @var RequestType|null */
    private $requestType;

    /** @var iterable|RequestTypeProviderInterface[] */
    private $requestTypeProviders;

    /** @var array [request type provider hash => bool, ...] */
    private $initializedRequestTypeProviders = [];

    /**
     * @param RequestStack                            $requestStack
     * @param iterable|RequestTypeProviderInterface[] $requestTypeProviders
     */
    public function __construct(RequestStack $requestStack, iterable $requestTypeProviders)
    {
        $this->requestStack = $requestStack;
        $this->requestTypeProviders = $requestTypeProviders;
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
                $providerHash = spl_object_hash($provider);
                if (!isset($this->initializedRequestTypeProviders[$providerHash])) {
                    if ($provider instanceof RestDocViewDetectorAwareInterface) {
                        $provider->setRestDocViewDetector($this);
                    }
                    $this->initializedRequestTypeProviders[$providerHash] = true;
                }
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

    /**
     * {@inheritDoc}
     */
    public function reset()
    {
        $this->setView();
        $this->initializedRequestTypeProviders = [];
    }
}
