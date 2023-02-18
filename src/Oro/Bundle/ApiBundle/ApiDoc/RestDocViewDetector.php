<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The class that helps to get the name of the current ApiDoc view, API version and the request type.
 */
class RestDocViewDetector implements ResetInterface
{
    private RequestStack $requestStack;
    /** @var iterable<RequestTypeProviderInterface> */
    private iterable $requestTypeProviders;
    private ?string $view = null;
    private ?string $version = null;
    private ?RequestType $requestType = null;
    /** @var array [request type provider hash => bool, ...] */
    private array $initializedRequestTypeProviders = [];

    /**
     * @param RequestStack                           $requestStack
     * @param iterable<RequestTypeProviderInterface> $requestTypeProviders
     */
    public function __construct(RequestStack $requestStack, iterable $requestTypeProviders)
    {
        $this->requestStack = $requestStack;
        $this->requestTypeProviders = $requestTypeProviders;
    }

    public function getView(): string
    {
        $view = $this->view;
        if (null === $view) {
            $view = '';
            $request = $this->requestStack->getMainRequest();
            if (null !== $request) {
                if ($request->attributes->has('view')) {
                    $view = $request->attributes->get('view');
                }
                $this->setView($view);
            }
        }

        return $view;
    }

    public function setView(string $view = null): void
    {
        $this->view = $view;
        $this->requestType = null;
        $this->version = null;
    }

    public function getVersion(): string
    {
        if (null === $this->version) {
            $this->version = Version::normalizeVersion(null);
        }

        return $this->version;
    }

    public function setVersion(string $version = null): void
    {
        $this->version = $version;
    }

    public function getRequestType(): RequestType
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
    public function reset(): void
    {
        $this->setView();
        $this->initializedRequestTypeProviders = [];
    }
}
