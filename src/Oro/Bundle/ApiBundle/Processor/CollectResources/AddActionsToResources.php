<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Config\ActionsConfigExtra;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Add info about excluded actions to resources.
 */
class AddActionsToResources implements ProcessorInterface
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
        /** @var CollectResourcesContext $context */

        $resources = $context->getResult();
        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        $actionsExtra = new ActionsConfigExtra();

        /** @var ApiResource $resource */
        foreach ($resources as $resource) {
            $config = $this->configProvider->getConfig(
                $resource->getEntityClass(),
                $version,
                $requestType,
                [$actionsExtra]
            );
            $resource->setExcludedActions($config->getActions()->getExcluded());
        }

        $context->setResult($resources);
    }
}
