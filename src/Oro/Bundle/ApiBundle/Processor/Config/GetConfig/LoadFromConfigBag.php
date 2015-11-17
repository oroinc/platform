<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;

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
        /** @var ConfigContext $context */

        if ($context->hasResult()) {
            // config is already set
            return;
        }

        $entityClass = $context->getClassName();
        if (!$entityClass) {
            // an entity type is not specified
            return;
        }

        $config = $this->configBag->getConfig($entityClass, $context->getVersion());
        if (null !== $config) {
            $context->setResult($config);
        }
    }
}
