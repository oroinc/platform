<?php

namespace Oro\Component\Layout\ExpressionLanguage;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataAccessorInterface;
use Oro\Component\Layout\Exception\CircularReferenceException;
use Oro\Component\Layout\ExpressionLanguage\Encoder\ExpressionEncoderRegistry;
use Oro\Component\Layout\OptionValueBag;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Node\NameNode;
use Symfony\Component\ExpressionLanguage\Node\Node;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * Processes layout expressions in the array of values
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ExpressionProcessor
{
    private const STRING_IS_REGULAR = 0;
    private const STRING_IS_EXPRESSION = 1;
    private const STRING_IS_EXPRESSION_STARTED_WITH_BACKSLASH = -1;

    /** @var ExpressionLanguage */
    protected $expressionLanguage;

    /** @var ExpressionEncoderRegistry */
    protected $encoderRegistry;

    /** @var  array */
    protected $values = [];

    /** @var  array */
    protected $processingValues = [];

    /** @var  array */
    protected $processedValues = [];

    /** @var boolean */
    protected $visible = true;

    public function __construct(
        ExpressionLanguage $expressionLanguage,
        ExpressionEncoderRegistry $encoderRegistry
    ) {
        $this->expressionLanguage = $expressionLanguage;
        $this->encoderRegistry = $encoderRegistry;
    }

    public function processExpressions(
        array &$values,
        ContextInterface $context,
        DataAccessorInterface $data = null,
        bool $evaluate = true,
        string $encoding = null
    ): void {
        if (!$evaluate && $encoding === null) {
            return;
        }

        $this->setValues($values);
        $this->processVisibleValue($values, $context, $data, $evaluate, $encoding);
        $this->processValues($values, $context, $data, $evaluate, $encoding);
    }

    protected function setValues(array $values): void
    {
        if (isset($values['data'])) {
            throw new \InvalidArgumentException('"data" should not be used as value key.');
        }
        if (isset($values['context'])) {
            throw new \InvalidArgumentException('"context" should not be used as value key.');
        }

        $this->values = $values;
        $this->processingValues = [];
        $this->processedValues = [];
    }

    protected function processVisibleValue(
        array $values,
        ContextInterface $context,
        ?DataAccessorInterface $data,
        bool $evaluate,
        ?string $encoding
    ): void {
        if (\array_key_exists('visible', $values)) {
            $this->visible = $values['visible'];
            $this->processRootValue('visible', $this->visible, $context, $data, $evaluate, $encoding);
        }
    }

    protected function processValues(
        array &$values,
        ContextInterface $context,
        ?DataAccessorInterface $data,
        bool $evaluate,
        ?string $encoding
    ): void {
        foreach ($values as $key => $value) {
            if (\array_key_exists($key, $this->processedValues)) {
                $value = $this->processedValues[$key];
            } else {
                $this->processRootValue($key, $value, $context, $data, $evaluate, $encoding);
            }
            $values[$key] = $value;
        }
    }

    protected function processRootValue(
        string $key,
        &$value,
        ContextInterface $context,
        ?DataAccessorInterface $data,
        bool $evaluate,
        ?string $encoding
    ): void {
        $this->processingValues[$key] = $key;
        $this->processValue($value, $context, $data, $evaluate, $encoding);
        $this->processedValues[$key] = $value;
        unset($this->processingValues[$key]);
    }

    protected function processValue(
        &$value,
        ContextInterface $context,
        ?DataAccessorInterface $data,
        bool $evaluate,
        ?string $encoding
    ): void {
        if (\is_string($value) && !empty($value)) {
            $this->processStringValue($value, $context, $data, $evaluate, $encoding);
        } elseif (\is_array($value)) {
            foreach ($value as $key => &$item) {
                $this->processValue($item, $context, $data, $evaluate, $encoding);
                $value[$key] = $item;
            }
            unset($item);
        } elseif ($value instanceof OptionValueBag) {
            $this->processOptionValueBag($value, $context, $data, $evaluate, $encoding);
        } elseif ($value instanceof ParsedExpression) {
            $value = $this->processExpression($value, $context, $data, $evaluate, $encoding);
        } elseif ($value instanceof ClosureWithExtraParams) {
            $value = $this->processClosureWithExtraParams($value, $context, $data, $evaluate, $encoding);
        } elseif ($value instanceof \Closure) {
            $value = $this->processClosure($value, $context, $data);
        }
    }

    protected function processStringValue(
        &$value,
        ContextInterface $context,
        ?DataAccessorInterface $data,
        bool $evaluate,
        ?string $encoding
    ): void {
        switch ($this->checkStringValue($value)) {
            case self::STRING_IS_EXPRESSION:
                if ($expr = $this->parseExpression($value)) {
                    $value = $this->processExpression($expr, $context, $data, $evaluate, $encoding);
                }
                break;
            case self::STRING_IS_EXPRESSION_STARTED_WITH_BACKSLASH:
                // the backslash (\) at the begin of the array key should be removed
                $value = substr($value, 1);
                break;
        }
    }

    /**
     * @param ParsedExpression           $expr
     * @param ContextInterface           $context
     * @param DataAccessorInterface|null $data
     * @param bool                       $evaluate
     * @param string|null                $encoding
     *
     * @return mixed|string|ParsedExpression
     *
     * @throws CircularReferenceException
     */
    protected function processExpression(
        ParsedExpression $expr,
        ContextInterface $context,
        ?DataAccessorInterface $data,
        bool $evaluate,
        ?string $encoding
    ) {
        if (!$this->visible) {
            return null;
        }

        $node = $expr->getNodes();
        $deps = $this->getNotProcessedDependencies($node);

        if ($data === null && $this->worksWithDataVariable($node)) {
            return $expr;
        }

        foreach ($deps as $key => $dep) {
            if (\in_array($key, $this->processingValues, true) && !\in_array($key, $this->processedValues, true)) {
                $path = implode(' > ', array_merge($this->processingValues, [$key]));
                throw new CircularReferenceException(
                    sprintf('Circular reference "%s" on expression "%s".', $path, (string)$expr)
                );
            }
            $this->processRootValue($key, $dep, $context, $data, $evaluate, $encoding);
        }
        $values = array_merge(['context' => $context, 'data' => $data], $this->values, $this->processedValues);

        return $evaluate
            ? $this->expressionLanguage->evaluate($expr, $values)
            : $this->encoderRegistry->get($encoding)->encodeExpr($expr);
    }

    /**
     * @param string $value
     *
     * @return int the checking result
     *             0  - the value is regular string
     *             1  - the value is an expression
     *             -1 - the value is string that starts with "\="
     *                  which should be replaces with "="
     */
    protected function checkStringValue(string $value): int
    {
        if (\is_string($value)) {
            $pos = strpos($value, '=');
            if ($pos === 0) {
                // expression
                return self::STRING_IS_EXPRESSION;
            }
            if ($pos === 1 && $value[0] === '\\') {
                // the backslash (\) at the begin of the array key should be removed
                return self::STRING_IS_EXPRESSION_STARTED_WITH_BACKSLASH;
            }
        }

        // regular string
        return self::STRING_IS_REGULAR;
    }

    protected function getNotProcessedDependencies(Node $node): array
    {
        $deps = [];
        if ($node instanceof NameNode) {
            $name = $node->attributes['name'];
            if (\array_key_exists($name, $this->values) &&
                !\array_key_exists($name, $this->processedValues)
            ) {
                $deps[$name] = $this->values[$name];
            }
        }
        $notProcessedDeps = [];
        foreach ($node->nodes as $childNode) {
            $notProcessedDeps[] = $this->getNotProcessedDependencies($childNode);
        }

        return array_merge($deps, ...$notProcessedDeps);
    }

    protected function parseExpression(string $value): ?ParsedExpression
    {
        $names = array_merge(['context', 'data'], array_keys($this->values));

        return $this->expressionLanguage->parse(substr($value, 1), $names) ?: null;
    }

    protected function worksWithDataVariable(Node $node): bool
    {
        if ($node instanceof NameNode && $node->attributes['name'] === 'data') {
            return true;
        }
        foreach ($node->nodes as $childNode) {
            if ($this->worksWithDataVariable($childNode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \Closure                   $value
     * @param ContextInterface           $context
     * @param DataAccessorInterface|null $data
     *
     * @return mixed
     */
    protected function processClosure(\Closure $value, ContextInterface $context, ?DataAccessorInterface $data)
    {
        return $this->visible ? $value($context, $data) : null;
    }

    /**
     * @param ClosureWithExtraParams     $value
     * @param ContextInterface           $context
     * @param DataAccessorInterface|null $data
     * @param bool                       $evaluate
     * @param string|null                $encoding
     *
     * @return mixed
     */
    protected function processClosureWithExtraParams(
        ClosureWithExtraParams $value,
        ContextInterface $context,
        ?DataAccessorInterface $data,
        bool $evaluate,
        ?string $encoding
    ) {
        if (!$this->visible) {
            return null;
        }

        $params = [];
        foreach ($value->getExtraParamNames() as $paramName) {
            if (!\array_key_exists($paramName, $this->processedValues)) {
                if (!\array_key_exists($paramName, $this->values)) {
                    throw new SyntaxError(
                        sprintf('Variable "%s" is not valid.', $paramName),
                        0,
                        $value->getExpression(),
                        $paramName,
                        array_keys($this->values)
                    );
                }
                $this->processRootValue($paramName, $this->values[$paramName], $context, $data, $evaluate, $encoding);
            }
            $params[$paramName] = $this->processedValues[$paramName];
        }

        $closure = $value->getClosure();

        return $closure($context, $data, ...array_values($params));
    }

    protected function processOptionValueBag(
        OptionValueBag $value,
        ContextInterface $context,
        ?DataAccessorInterface $data,
        bool $evaluate,
        ?string $encoding
    ): void {
        foreach ($value->all() as $action) {
            $args = $action->getArguments();
            foreach ($args as $index => $arg) {
                $this->processValue($arg, $context, $data, $evaluate, $encoding);
                $action->setArgument($index, $arg);
            }
        }
    }
}
