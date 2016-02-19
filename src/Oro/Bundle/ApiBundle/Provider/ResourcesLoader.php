<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResourcesProcessor;
use Oro\Bundle\ApiBundle\Request\ApiResource;

class ResourcesLoader
{
    /** @var CollectResourcesProcessor */
    protected $processor;

    /**
     * @param CollectResourcesProcessor $processor
     */
    public function __construct(CollectResourcesProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Gets all public resources available for the requested API version.
     *
     * @param string   $version     The version of API
     * @param string[] $requestType The type of API request, for example "rest", "soap", "odata", etc.
     *
     * @return ApiResource[]
     */
    public function getResources($version, array $requestType)
    {
        /** @var CollectResourcesContext $context */
        $context = $this->processor->createContext();
        $context->setVersion($version);
        $context->setRequestType($requestType);

        $this->processor->process($context);

        return array_values($context->getResult()->toArray());
    }
}
