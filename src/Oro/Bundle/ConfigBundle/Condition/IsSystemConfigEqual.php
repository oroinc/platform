<?php

namespace Oro\Bundle\ConfigBundle\Condition;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * Check that System Configuration has needed value.
 * Usage:
 * @is_system_config_equal: ['some_config_path', 'needed value']
 */
class IsSystemConfigEqual extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'is_system_config_equal';

    /** @var ConfigManager */
    protected $configManager;

    /** @var string */
    protected $key;

    /** @var mixed */
    protected $value;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (2 !== count($options)) {
            throw new InvalidArgumentException(
                sprintf('Options must have 2 elements, but %d given.', count($options))
            );
        }

        if (array_key_exists('key', $options)) {
            $this->key = $options['key'];
        } elseif (array_key_exists(0, $options)) {
            $this->key = $options[0];
        } else {
            throw new InvalidArgumentException('Option "key" is required.');
        }

        if (array_key_exists('value', $options)) {
            $this->value = $options['value'];
        } elseif (array_key_exists(1, $options)) {
            $this->value = $options[1];
        } else {
            throw new InvalidArgumentException('Option "value" is required.');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        return $this->configManager->get($this->key) === $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->key, $this->value]);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->key, $this->value], $factoryAccessor);
    }
}
