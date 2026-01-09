<?php

namespace Oro\Bundle\LayoutBundle\ConfigExpression;

use Oro\Component\ConfigExpression\Exception;
use Oro\Component\ConfigExpression\Func\AbstractFunction;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Retrieves values from the layout context with optional default fallback.
 *
 * This config expression function provides access to layout context variables
 * using property path notation. It supports an optional default value that is
 * returned when the requested context value is null or undefined.
 */
class GetContextValue extends AbstractFunction
{
    public const PROPERTY_PATH_PREFIX = 'context.';

    /** @var mixed */
    protected $value;

    /** @var mixed */
    protected $default;

    /** @var bool */
    protected $hasDefault = false;

    #[\Override]
    public function getName()
    {
        return 'context';
    }

    #[\Override]
    public function toArray()
    {
        $params = [$this->value];
        if ($this->hasDefault) {
            $params[] = $this->default;
        }

        return $this->convertToArray($params);
    }

    #[\Override]
    public function compile($factoryAccessor)
    {
        $params = [$this->value];
        if ($this->hasDefault) {
            $params[] = $this->default;
        }

        return $this->convertToPhpCode($params, $factoryAccessor);
    }

    #[\Override]
    public function initialize(array $options)
    {
        $count = count($options);
        if ($count >= 1 && $count <= 2) {
            $this->value = reset($options);
            if (!is_string($this->value)) {
                throw new Exception\InvalidArgumentException(
                    sprintf('The first option should be a string, but %s given.', gettype($this->value))
                );
            }
            if ($count > 1) {
                $this->default = next($options);
                $this->hasDefault = true;
            }
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 1 or 2 elements, but %d given.', count($options))
            );
        }

        return $this;
    }

    #[\Override]
    protected function doEvaluate($context)
    {
        if ($this->hasDefault) {
            $value = $this->resolveValue($context, $this->createPropertyPath($this->value), false);

            return null !== $value
                ? $value
                : $this->resolveValue($context, $this->default);
        }

        return $this->resolveValue($context, $this->createPropertyPath($this->value));
    }

    /**
     * @param string $value
     *
     * @return PropertyPath
     */
    protected function createPropertyPath($value)
    {
        return new PropertyPath(self::PROPERTY_PATH_PREFIX . $value);
    }
}
