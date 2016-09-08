<?php

namespace Oro\Bundle\FeatureToggleBundle\ConfigExpression;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPath;

class FeatureResourceEnabled extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    /**
     * @var FeatureChecker
     */
    protected $featureChecker;

    /**
     * @var string|PropertyPath
     */
    protected $resource;

    /**
     * @var string|PropertyPath
     */
    protected $resourceType;

    /**
     * @var int|null|object|PropertyPath
     */
    protected $scopeIdentifier;

    /**
     * @param FeatureChecker $featureChecker
     */
    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'feature_resource_enabled';
    }

    /**
     * {@inheritdoc}
     *
     * Configuration examples:
     *
     * '@feature_resource_enabled': ['some_route', 'route']
     * '@feature_resource_enabled': ['resource': 'some_route', 'resource_type': 'route', 'scope_identifier': $.scopeId]
     */
    public function initialize(array $options)
    {
        if (array_key_exists('resource', $options)) {
            $this->resource = $options['resource'];
            if (array_key_exists('resource_type', $options)) {
                $this->resourceType = $options['resource_type'];
            } else {
                throw new InvalidArgumentException('Options "resource_type" is required');
            }
            if (array_key_exists('scope_identifier', $options)) {
                $this->scopeIdentifier = $options['scope_identifier'];
            }
        } elseif (count($options) > 1) {
            $this->resource = reset($options);
            $this->resourceType = next($options);
            if (count($options) === 3) {
                $this->scopeIdentifier = next($options);
            }
        }

        if (!$this->resource || !$this->resourceType) {
            throw new InvalidArgumentException('Options "resource" and "resource_type" are required');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $params = [$this->resource, $this->resourceType];
        if ($this->scopeIdentifier !== null) {
            $params[] = $this->scopeIdentifier;
        }

        return $this->convertToArray($params);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        $params = [$this->resource, $this->resourceType];
        if ($this->scopeIdentifier !== null) {
            $params[] = $this->scopeIdentifier;
        }

        return $this->convertToPhpCode($params, $factoryAccessor);
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $resource = $this->resolveValue($context, $this->resource);
        $resourceType = $this->resolveValue($context, $this->resourceType);
        $scopeIdentifier = $this->resolveValue($context, $this->scopeIdentifier);

        return $this->featureChecker->isResourceEnabled($resource, $resourceType, $scopeIdentifier);
    }
}
