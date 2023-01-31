<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\Extra\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets initial value for all configuration sections which are not loaded yet.
 */
class EnsureInitialized implements ProcessorInterface
{
    private ConfigLoaderFactory $configLoaderFactory;

    public function __construct(ConfigLoaderFactory $configLoaderFactory)
    {
        $this->configLoaderFactory = $configLoaderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        if (!$context->hasResult()) {
            $context->setResult(
                $this->configLoaderFactory->getLoader(ConfigUtil::DEFINITION)->load([])
            );
        }
        $definition = $context->getResult();
        $context->setRequestedExclusionPolicy($definition->getExclusionPolicy());
        $context->setExplicitlyConfiguredFieldNames(array_keys($definition->getFields()));

        $extras = $context->getExtras();
        if ($context->hasExtra(SortersConfigExtra::NAME) && !$context->getResult()->isSortingEnabled()) {
            $context->removeExtra(SortersConfigExtra::NAME);
            $extras = $context->getExtras();
        }

        foreach ($extras as $extra) {
            if ($extra instanceof ConfigExtraSectionInterface) {
                $sectionName = $extra->getName();
                if (!$context->has($sectionName)) {
                    $context->set(
                        $sectionName,
                        $this->configLoaderFactory->getLoader($extra->getConfigType())->load([])
                    );
                }
            }
        }
    }
}
