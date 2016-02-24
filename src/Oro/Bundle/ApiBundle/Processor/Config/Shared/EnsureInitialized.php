<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

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
