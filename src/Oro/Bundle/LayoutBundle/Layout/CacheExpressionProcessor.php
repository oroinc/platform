<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataAccessorInterface;
use Oro\Component\Layout\ExpressionLanguage\ClosureWithExtraParams;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

/**
 * Overrides ExpressionProcessor to not process expression in options of cached blocks.
 */
class CacheExpressionProcessor extends ExpressionProcessor
{
    private bool $cached = false;

    /**
     * {@inheritDoc}
     */
    public function processExpressions(
        array &$values,
        ContextInterface $context,
        DataAccessorInterface $data = null,
        $evaluate = true,
        $encoding = null
    ): void {
        if (!$evaluate && $encoding === null) {
            return;
        }

        $this->setValues($values);
        $this->processVisibleValue($values, $context, $data, $evaluate, $encoding);
        $this->cached = $values['_cached'] ?? false;
        $this->processValues($values, $context, $data, $evaluate, $encoding);
    }

    /**
     * {@inheritDoc}
     */
    protected function processExpression(
        ParsedExpression $expr,
        ContextInterface $context,
        ?DataAccessorInterface $data,
        bool $evaluate,
        ?string $encoding
    ) {
        if ($this->cached) {
            return null;
        }

        return parent::processExpression($expr, $context, $data, $evaluate, $encoding);
    }

    /**
     * {@inheritDoc}
     */
    protected function processClosure(\Closure $value, ContextInterface $context, ?DataAccessorInterface $data)
    {
        if ($this->cached) {
            return null;
        }

        return parent::processClosure($value, $context, $data);
    }

    /**
     * {@inheritDoc}
     */
    protected function processClosureWithExtraParams(
        ClosureWithExtraParams $value,
        ContextInterface $context,
        ?DataAccessorInterface $data,
        bool $evaluate,
        ?string $encoding
    ) {
        if ($this->cached) {
            return null;
        }

        return parent::processClosureWithExtraParams($value, $context, $data, $evaluate, $encoding);
    }
}
