<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;

/**
 * Gets metadata from a configuration file.
 */
class LoadFromConfigBag implements ProcessorInterface
{
    /** @var ConfigBag */
    protected $configBag;

    /**
     * @param ConfigBag $configBag
     */
    public function __construct(ConfigBag $configBag)
    {
        $this->configBag = $configBag;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var MetadataContext $context */

        if ($context->hasResult()) {
            // config is already set
            return;
        }

        $config = $this->configBag->getMetadata($context->getClassName(), $context->getVersion());
        if (!empty($config)) {
            $context->setResult($config);
        }
    }
}
