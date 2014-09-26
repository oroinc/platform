<?php

namespace Oro\Bundle\DataGridBundle\Extension\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Exception\LogicException;

abstract class AbstractLocalizedProperty extends FieldProperty
{
    const FORMATTER_METHOD = 'method';
    const RESOLVER_KEY     = 'context_resolver';
    const CONTEXT_KEY      = 'context';

    /**
     * {@inheritdoc}
     */
    protected function getRawValue(ResultRecordInterface $record)
    {
        $value = parent::getRawValue($record);

        $context = $this->getOr(self::CONTEXT_KEY, []);
        if (!is_array($context)) {
            $context = [$context];
        }
        $resolver = $this->getOr(self::RESOLVER_KEY, false);
        if (is_callable($resolver)) {
            $context = array_merge($context, $resolver($record, $value, $this->getFormatter()));
        }
        array_unshift($context, $value);

        $method = $this->get(self::FORMATTER_METHOD);
        if (!method_exists($this->getFormatter(), $method)) {
            throw new LogicException('Given method does not exist');
        }

        return call_user_func_array([$this->getFormatter(), $method], $context);
    }

    /**
     * @return mixed
     */
    abstract protected function getFormatter();
}
