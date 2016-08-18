<?php

namespace Oro\Bundle\LayoutBundle\Layout\Processor;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataAccessorInterface;
use Oro\Component\Layout\OptionValueBag;

use Oro\Bundle\LayoutBundle\Layout\Encoder\ExpressionEncoderRegistry;

class ExpressionProcessor
{
    const STRING_IS_REGULAR = 0;
    const STRING_IS_EXPRESSION = 1;
    const STRING_IS_EXPRESSION_STARTED_WITH_BACKSLASH = -1;

    /** @var ExpressionLanguage */
    protected $expressionLanguage;

    /** @var ExpressionEncoderRegistry */
    protected $encoderRegistry;

    /**
     * @param ExpressionLanguage              $expressionLanguage
     * @param ExpressionEncoderRegistry $encoderRegistry
     */
    public function __construct(
        ExpressionLanguage $expressionLanguage,
        ExpressionEncoderRegistry $encoderRegistry
    ) {
        $this->expressionLanguage = $expressionLanguage;
        $this->encoderRegistry = $encoderRegistry;
    }

    /**
     * @param array                 $values
     * @param ContextInterface      $context
     * @param bool                  $evaluate
     * @param string                $encoding
     * @param DataAccessorInterface $data
     */
    public function processExpressions(
        array &$values,
        ContextInterface $context,
        $evaluate,
        $encoding,
        DataAccessorInterface $data = null
    ) {
        if (!$evaluate && $encoding === null) {
            return;
        }
        foreach ($values as $key => &$value) {
            if (is_string($value)) {
                if (!empty($value)) {
                    switch ($this->checkStringValue($value)) {
                        case self::STRING_IS_REGULAR:
                            break;
                        case self::STRING_IS_EXPRESSION:
                            $value = $this->processExpression(
                                $this->expressionLanguage->parse(substr($value, 1), ['context', 'data']),
                                $context,
                                $evaluate,
                                $encoding,
                                $data
                            );
                            break;
                        case self::STRING_IS_EXPRESSION_STARTED_WITH_BACKSLASH:
                            // the backslash (\) at the begin of the array key should be removed
                            $value = substr($value, 1);
                            break;
                    }
                }
            } elseif (is_array($value)) {
                $this->processExpressions($value, $context, $evaluate, $encoding, $data);
            } elseif ($value instanceof OptionValueBag) {
                foreach ($value->all() as $action) {
                    $args = $action->getArguments();
                    $this->processExpressions($args, $context, $evaluate, $encoding, $data);
                    foreach ($args as $index => $arg) {
                        $action->setArgument($index, $arg);
                    }
                }
            } elseif ($value instanceof ParsedExpression) {
                $value = $this->processExpression($value, $context, $evaluate, $encoding, $data);
            }
        }
    }

    /**
     * @param ParsedExpression      $expr
     * @param ContextInterface      $context
     * @param bool                  $evaluate
     * @param string                $encoding
     * @param DataAccessorInterface $data
     *
     * @return mixed|string|ParsedExpression
     */
    protected function processExpression(
        ParsedExpression $expr,
        ContextInterface $context,
        $evaluate,
        $encoding,
        DataAccessorInterface $data = null
    ) {
        $node = $expr->getNodes();
        if ($data === null && $this->nodeWorkWithData($node)) {
            return $expr;
        }

        return $evaluate
            ? $this->expressionLanguage->evaluate($expr, ['context' => $context, 'data' => $data])
            : $this->encoderRegistry->getEncoder($encoding)->encodeExpr($expr);
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
    protected function checkStringValue($value)
    {
        if (is_string($value)) {
            $pos = strpos($value, '=');
            if ($pos === 0) {
                // expression
                return self::STRING_IS_EXPRESSION;
            } elseif ($pos === 1 && $value[0] === '\\') {
                // the backslash (\) at the begin of the array key should be removed
                return self::STRING_IS_EXPRESSION_STARTED_WITH_BACKSLASH;
            }
        }

        // regular string
        return self::STRING_IS_REGULAR;
    }

    /**
     * @param $node
     */
    protected function nodeWorkWithData($node)
    {
        //TO DO realisation
    }
}
