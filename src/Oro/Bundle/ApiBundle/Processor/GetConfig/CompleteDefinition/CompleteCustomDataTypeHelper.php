<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

/**
 * The helper class to complete the configuration of different kind of fields with custom data-types.
 */
class CompleteCustomDataTypeHelper
{
    /** @var array [[completer service id, request type expression], ...] */
    private array $customDataTypeCompleters;
    private ContainerInterface $container;
    private RequestExpressionMatcher $matcher;
    /** @var array [request type => [service id, ...], ...] */
    private array $cache = [];

    /**
     * @param array                    $customDataTypeCompleters [[completer service id, request type expression], ...]
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(
        array $customDataTypeCompleters,
        ContainerInterface $container,
        RequestExpressionMatcher $matcher
    ) {
        $this->customDataTypeCompleters = $customDataTypeCompleters;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    public function completeCustomDataTypes(
        EntityDefinitionConfig $definition,
        ClassMetadata $metadata,
        string $version,
        RequestType $requestType
    ): void {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            $dataType = $field->getDataType();
            if ($dataType) {
                $this->completeCustomDataType(
                    $definition,
                    $metadata,
                    $fieldName,
                    $field,
                    $dataType,
                    $version,
                    $requestType
                );
            }
        }
    }

    public function completeCustomDataType(
        EntityDefinitionConfig $definition,
        ClassMetadata $metadata,
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        string $dataType,
        string $version,
        RequestType $requestType
    ): void {
        $completers = $this->getCustomDataTypeCompleters($requestType);
        foreach ($completers as $serviceId) {
            /** @var CustomDataTypeCompleterInterface $completer */
            $completer = $this->container->get($serviceId);
            $isCompleted = $completer->completeCustomDataType(
                $metadata,
                $definition,
                $fieldName,
                $field,
                $dataType,
                $version,
                $requestType
            );
            if ($isCompleted) {
                break;
            }
        }
    }

    /**
     * @param RequestType $requestType
     *
     * @return string[] [service id, ...]
     */
    private function getCustomDataTypeCompleters(RequestType $requestType): array
    {
        $cacheKey = (string)$requestType;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $completers = [];
        foreach ($this->customDataTypeCompleters as [$serviceId, $expression]) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $completers[] = $serviceId;
            }
        }
        $this->cache[$cacheKey] = $completers;

        return $completers;
    }
}
