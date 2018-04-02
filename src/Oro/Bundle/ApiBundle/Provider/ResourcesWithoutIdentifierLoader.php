<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Loads a list of resources that do not have an identifier.
 */
class ResourcesWithoutIdentifierLoader
{
    /** @var ActionProcessorBagInterface */
    private $processorBag;

    /**
     * @param ActionProcessorBagInterface $processorBag
     */
    public function __construct(ActionProcessorBagInterface $processorBag)
    {
        $this->processorBag = $processorBag;
    }

    /**
     * @param string        $version
     * @param RequestType   $requestType
     * @param ApiResource[] $resources
     *
     * @return string[] The list of class names
     */
    public function load(string $version, RequestType $requestType, array $resources): array
    {
        $resourcesWithoutIdentifier = [];
        foreach ($resources as $resource) {
            $entityClass = $resource->getEntityClass();
            if (!$this->hasIdentifierFields($entityClass, $version, $requestType)) {
                $resourcesWithoutIdentifier[] = $entityClass;
            }
        }

        return $resourcesWithoutIdentifier;
    }

    /**
     * @param string      $entityClass
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return bool
     */
    private function hasIdentifierFields(string $entityClass, string $version, RequestType $requestType): bool
    {
        $processor = $this->processorBag->getProcessor(ApiActions::GET);
        /** @var Context $context */
        $context = $processor->createContext();
        $context->setClassName($entityClass);
        $context->setVersion($version);
        $context->getRequestType()->set($requestType);
        $context->addConfigExtra(new EntityDefinitionConfigExtra($context->getAction()));
        $context->addConfigExtra(new FilterIdentifierFieldsConfigExtra());
        $context->setLastGroup('initialize');

        $processor->process($context);
        $config = $context->getConfig();

        $result = false;
        if (null !== $config) {
            $idFieldNames = $config->getIdentifierFieldNames();
            if (!empty($idFieldNames)) {
                $result = true;
            }
        }

        return $result;
    }
}
