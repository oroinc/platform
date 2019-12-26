<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides the request type for the current API view.
 */
class RestRequestTypeProvider implements
    RequestTypeProviderInterface,
    RestDocViewDetectorAwareInterface,
    ResetInterface
{
    /** @var array [view name => [request type, ...], ...] */
    private $requestTypeMap = [];

    /** @var RestDocViewDetector|null */
    private $docViewDetector;

    /**
     * Adds a mapping between API view and related to it request type.
     *
     * @param string   $view
     * @param string[] $requestTypes
     */
    public function mapViewToRequestType(string $view, array $requestTypes)
    {
        $this->requestTypeMap[$view] = $requestTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function setRestDocViewDetector(RestDocViewDetector $docViewDetector): void
    {
        $this->docViewDetector = $docViewDetector;
    }

    /**
     * {@inheritDoc}
     */
    public function reset()
    {
        $this->docViewDetector = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestType(): ?RequestType
    {
        $view = $this->docViewDetector->getView();
        if (array_key_exists($view, $this->requestTypeMap)) {
            return new RequestType($this->requestTypeMap[$view]);
        }

        return null;
    }
}
