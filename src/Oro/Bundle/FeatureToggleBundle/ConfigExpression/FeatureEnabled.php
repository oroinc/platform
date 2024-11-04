<?php

namespace Oro\Bundle\FeatureToggleBundle\ConfigExpression;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPath;

class FeatureEnabled extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    /**
     * @var FeatureChecker
     */
    protected $featureChecker;

    /**
     * @var string|PropertyPath
     */
    protected $feature;

    /**
     * @var int|null|object|PropertyPath
     */
    protected $scopeIdentifier;

    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    #[\Override]
    public function getName()
    {
        return 'feature_enabled';
    }

    #[\Override]
    public function initialize(array $options)
    {
        $optionsCount = count($options);
        if ($optionsCount > 0 && $optionsCount < 3) {
            if (array_key_exists('feature', $options) || array_key_exists(0, $options)) {
                $this->feature = array_key_exists('feature', $options) ? $options['feature'] : $options[0];
            }
            if (array_key_exists('scope_identifier', $options) || array_key_exists(1, $options)) {
                $this->scopeIdentifier = array_key_exists('scope_identifier', $options)
                    ? $options['scope_identifier']
                    : $options[1];
            }
        } else {
            throw new InvalidArgumentException('Option "feature" is required');
        }

        return $this;
    }

    #[\Override]
    public function toArray()
    {
        $params = [$this->feature];
        if ($this->scopeIdentifier !== null) {
            $params[] = $this->scopeIdentifier;
        }

        return $this->convertToArray($params);
    }

    #[\Override]
    public function compile($factoryAccessor)
    {
        $params = [$this->feature];
        if ($this->scopeIdentifier !== null) {
            $params[] = $this->scopeIdentifier;
        }

        return $this->convertToPhpCode($params, $factoryAccessor);
    }

    #[\Override]
    protected function isConditionAllowed($context)
    {
        $feature = $this->resolveValue($context, $this->feature);
        $scopeIdentifier = $this->resolveValue($context, $this->scopeIdentifier);

        return $this->featureChecker->isFeatureEnabled($feature, $scopeIdentifier);
    }
}
