<?php

namespace Oro\Component\ConfigExpression\Condition;

use Oro\Component\ConfigExpression\Exception;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class Type extends AbstractComparison
{
    const NAME = 'type';

    #[\Override]
    public function initialize(array $options)
    {
        parent::initialize($options);

        if (!$this->left instanceof PropertyPathInterface) {
            throw new Exception\InvalidArgumentException('Option "left" must be property path.');
        }

        return $this;
    }

    #[\Override]
    protected function doCompare($value, $type)
    {
        return gettype($value) === $type || $value instanceof $type;
    }

    #[\Override]
    protected function getMessageParameters($context)
    {
        $left = $this->resolveValue($context, $this->left);

        if (is_object($left)) {
            $value = sprintf('(object)%s', get_class($left));
        } elseif (!is_scalar($left)) {
            $value = gettype($left);
        } else {
            $value = sprintf('(%s)%s', gettype($left), $left);
        }

        return [
            '{{ value }}'  => $value,
            '{{ type }}' => $this->resolveValue($context, $this->right),
        ];
    }

    #[\Override]
    public function getName()
    {
        return self::NAME;
    }
}
