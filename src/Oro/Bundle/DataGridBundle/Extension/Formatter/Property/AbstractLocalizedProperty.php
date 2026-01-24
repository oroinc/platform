<?php

namespace Oro\Bundle\DataGridBundle\Extension\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Exception\LogicException;

/**
 * Provides common functionality for datagrid properties that require localization formatting.
 *
 * This base class implements value formatting using a configurable formatter method with context resolution.
 * It allows properties to format values using localization-aware formatters (e.g., number, date, currency formatters)
 * with dynamic context. Subclasses must provide the formatter instance.
 */
abstract class AbstractLocalizedProperty extends FieldProperty
{
    const FORMATTER_METHOD = 'method';
    const RESOLVER_KEY     = 'context_resolver';
    const CONTEXT_KEY      = 'context';

    #[\Override]
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
