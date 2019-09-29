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
    /** @var ConfigLoaderFactory */
    protected $configLoaderFactory;

    /**
     * @param ConfigLoaderFactory $configLoaderFactory
     */
    public function __construct(ConfigLoaderFactory $configLoaderFactory)
    {
        $this->configLoaderFactory = $configLoaderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        if (!$context->hasResult()) {
            $context->setResult(
                $this->configLoaderFactory->getLoader(ConfigUtil::DEFINITION)->load([])
            );
        }

        $extras = $context->getExtras();
        if (!$context->getResult()->isSortingEnabled() && $context->hasExtra(SortersConfigExtra::NAME)) {
            $context->removeExtra(SortersConfigExtra::NAME);
            $extras = $context->getExtras();
        }

        foreach ($extras as $extra) {
            $sectionName = $extra->getName();
            if ($extra instanceof ConfigExtraSectionInterface && !$context->has($sectionName)) {
                $context->set(
                    $sectionName,
                    $this->configLoaderFactory->getLoader($extra->getConfigType())->load([])
                );
            }
        }
    }
}
