<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

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
        /** @var GetListContext $context */

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
            $context->getRequestType(),
            $context->getAction()
        );
        if (null !== $config) {
            if (isset($config[ConfigUtil::DEFINITION])) {
                $context->setConfig($config[ConfigUtil::DEFINITION]);
            }
            if (isset($config[ConfigUtil::FILTERS]) && null === $context->getConfigOfFilters()) {
                $context->setConfigOfFilters($config[ConfigUtil::FILTERS]);
            }
            if (isset($config[ConfigUtil::SORTERS]) && null === $context->getConfigOfSorters()) {
                $context->setConfigOfSorters($config[ConfigUtil::SORTERS]);
            }
        }
    }
}
