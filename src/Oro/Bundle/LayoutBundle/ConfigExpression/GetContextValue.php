<?php


namespace Oro\Bundle\LayoutBundle\ConfigExpression;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\ConfigExpression\Exception;
use Oro\Component\ConfigExpression\Func\AbstractFunction;

class GetContextValue extends AbstractFunction
{
    const PROPERTY_PATH_PREFIX = 'context.';

    /** @var mixed */
    protected $value;

    /** @var mixed */
    protected $default;

    /** @var bool */
    protected $hasDefault = false;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'context';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $params = [$this->value];
        if ($this->hasDefault) {
            $params[] = $this->default;
        }

        return $this->convertToArray($params);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        $params = [$this->value];
        if ($this->hasDefault) {
            $params[] = $this->default;
        }

        return $this->convertToPhpCode($params, $factoryAccessor);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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
        return new PropertyPath(self::PROPERTY_PATH_PREFIX.$value);
    }
}
