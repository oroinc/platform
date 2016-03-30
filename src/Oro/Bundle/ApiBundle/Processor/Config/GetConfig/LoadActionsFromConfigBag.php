<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\ActionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;

/**
 * Loads configuration of the "actions" section from "Resources/config/oro/api.yml".
 */
class LoadActionsFromConfigBag implements ProcessorInterface
{
    /** @var ConfigLoaderFactory */
    protected $configLoaderFactory;

    /** @var ConfigBag */
    protected $configBag;

    /**
     * @param ConfigLoaderFactory $configLoaderFactory
     * @param ConfigBag           $configBag
     */
    public function __construct(
        ConfigLoaderFactory $configLoaderFactory,
        ConfigBag $configBag
    ) {
        $this->configLoaderFactory = $configLoaderFactory;
        $this->configBag = $configBag;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $sectionName = ActionsConfigExtra::NAME;
        if ($context->has($sectionName)) {
            // the "actions" section already loaded
            return;
        }

        $config = $this->configBag->getConfig($context->getClassName(), $context->getVersion());
        if (null !== $config && !empty($config[$sectionName])) {
            /** @var ConfigExtraSectionInterface $actionsExtra */
            $actionsExtra = $context->getExtra($sectionName);
            $actionsLoader = $this->configLoaderFactory->getLoader($actionsExtra->getConfigType());
            $context->set($sectionName, $actionsLoader->load($config[$sectionName]));
        }
    }
}
