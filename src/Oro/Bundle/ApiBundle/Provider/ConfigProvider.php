<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Processor\GetConfig\GetConfigContext;
use Oro\Bundle\ApiBundle\Processor\GetConfigProcessor;

class ConfigProvider
{
    const LATEST_VERSION = 'latest';

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
     * Gets a config for the given class version
     *
     * @param string $className The FQCN of an entity
     * @param string $version   The version of a config
     *
     * @return array|null
     */
    public function getConfig($className, $version)
    {
        $context = new GetConfigContext();
        $context->setAction('get_config');
        $context->setClassName($className);
        if ($version !== self::LATEST_VERSION) {
            $context->setVersion($version);
        }

        $this->processor->process($context);

        return $context->getResult();
    }
}
