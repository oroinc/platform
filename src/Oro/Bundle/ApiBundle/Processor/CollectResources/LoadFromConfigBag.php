<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Request\ApiResource;

/**
 * Loads resources for all entities configured in "Resources/config/oro/api.yml".
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
        /** @var CollectResourcesContext $context */

        $resources = $context->getResult();
        $configs = $this->configBag->getConfigs($context->getVersion());
        foreach ($configs as $entityClass => $config) {
            if (!$resources->has($entityClass)) {
                $resources->add(new ApiResource($entityClass));
            }
        }
    }
}
