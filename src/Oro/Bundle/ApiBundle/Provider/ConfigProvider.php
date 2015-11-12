<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Processor\GetConfig\GetConfigContext;
use Oro\Bundle\ApiBundle\Processor\GetConfigProcessor;

class ConfigProvider
{
    /** @var GetConfigProcessor */
    protected $processor;

    /**
     * @param GetConfigProcessor $processor
     */
    public function __construct(GetConfigProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Gets a config for the given version of an entity.
     *
     * @param string      $className   The FQCN of an entity
     * @param string      $version     The version of a config
     * @param string|null $requestType The type of API request, for example "rest", "soap", "odata", etc.
     *
     * @return array|null
     */
    public function getConfig($className, $version, $requestType = null)
    {
        /** @var GetConfigContext $context */
        $context = $this->processor->createContext();
        $context->setAction('get_config');
        if (null !== $requestType) {
            $context->setRequestType($requestType);
        }
        $context->setClassName($className);
        if ($version !== $context::LATEST_VERSION) {
            $context->setVersion($version);
        }

        $this->processor->process($context);

        return $context->getResult();
    }
}
