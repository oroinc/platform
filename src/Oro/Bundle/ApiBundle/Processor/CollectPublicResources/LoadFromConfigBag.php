<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectPublicResources;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Request\PublicResource;

/**
 * Collects resources for all entities configured in 'Resources/config/oro/api.yml'.
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
        /** @var CollectPublicResourcesContext $context */

        $resources = $context->getResult();
        $configs   = $this->configBag->getConfigs($context->getVersion());
        foreach ($configs as $entityClass => $config) {
            $resources->add(new PublicResource($entityClass));
        }
    }
}
