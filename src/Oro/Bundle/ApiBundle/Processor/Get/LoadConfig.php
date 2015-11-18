<?php

namespace Oro\Bundle\ApiBundle\Processor\Get;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class LoadConfig implements ProcessorInterface
{
    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetContext $context */

        $config = $context->getConfig();
        if (null !== $config) {
            // an entity configuration is already loaded
            return;
        }

        $entityClass = $context->getClassName();
        if (!$entityClass) {
            // an entity type is not specified
            return;
        }

        $config = $this->configProvider->getConfig(
            $entityClass,
            $context->getVersion(),
            $context->getRequestType()
        );
        if (null !== $config && isset($config[ConfigUtil::DEFINITION])) {
            $context->setConfig($config[ConfigUtil::DEFINITION]);
        }
    }
}
