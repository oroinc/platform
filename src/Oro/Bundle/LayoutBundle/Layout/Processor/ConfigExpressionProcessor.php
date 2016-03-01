<?php

namespace Oro\Bundle\LayoutBundle\Layout\Processor;

use Oro\Component\ConfigExpression\AssemblerInterface;
use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataAccessorInterface;
use Oro\Component\Layout\OptionValueBag;

use Oro\Bundle\LayoutBundle\Layout\Encoder\ConfigExpressionEncoderRegistry;

class ConfigExpressionProcessor
{
    const ARRAY_IS_REGULAR = 0;
    const ARRAY_IS_EXPRESSION = 1;
    const ARRAY_IS_EXPRESSION_STARTED_WITH_BACKSLASH = -1;

    /** @var AssemblerInterface */
    protected $expressionAssembler;

    /** @var ConfigExpressionEncoderRegistry */
    protected $encoderRegistry;

    /**
     * @param AssemblerInterface              $expressionAssembler
     * @param ConfigExpressionEncoderRegistry $encoderRegistry
     */
    public function __construct(
        AssemblerInterface $expressionAssembler,
        ConfigExpressionEncoderRegistry $encoderRegistry
    ) {
        $this->expressionAssembler = $expressionAssembler;
        $this->encoderRegistry     = $encoderRegistry;
    }

    /**
     * @param array                 $values
     * @param ContextInterface      $context
     * @param DataAccessorInterface $data
     * @param bool                  $evaluate
     * @param string                $encoding
     */
    public function processExpressions(
        array &$values,
        ContextInterface $context,
        DataAccessorInterface $data,
        $evaluate,
        $encoding
    ) {
        if (!$evaluate && $encoding === null) {
            return;
        }
        foreach ($values as $key => &$value) {
            if (is_array($value)) {
                if (!empty($value)) {
                    switch ($this->checkArrayValue($value)) {
                        case self::ARRAY_IS_REGULAR:
                            $this->processExpressions($value, $context, $data, $evaluate, $encoding);
                            break;
                        case self::ARRAY_IS_EXPRESSION:
                            $value = $this->processExpression(
                                $this->expressionAssembler->assemble($value),
                                $context,
                                $data,
                                $evaluate,
                                $encoding
                            );
                            break;
                        case self::ARRAY_IS_EXPRESSION_STARTED_WITH_BACKSLASH:
                            // the backslash (\) at the begin of the array key should be removed
                            $value = [substr(key($value), 1) => reset($value)];
                            break;
                    }
                }
            } elseif ($value instanceof OptionValueBag) {
                foreach ($value->all() as $action) {
                    $args = $action->getArguments();
                    $this->processExpressions($args, $context, $data, $evaluate, $encoding);
                    foreach ($args as $index => $arg) {
                        $action->setArgument($index, $arg);
                    }
                }
            } elseif ($value instanceof ExpressionInterface) {
                $value = $this->processExpression($value, $context, $data, $evaluate, $encoding);
            }
        }
    }

    /**
     * @param ExpressionInterface   $expr
     * @param ContextInterface      $context
     * @param DataAccessorInterface $data
     * @param bool                  $evaluate
     * @param string                $encoding
     *
     * @return mixed|string
     */
    protected function processExpression(
        ExpressionInterface $expr,
        ContextInterface $context,
        DataAccessorInterface $data,
        $evaluate,
        $encoding
    ) {
        return $evaluate
            ? $expr->evaluate(['context' => $context, 'data' => $data])
            : $this->encoderRegistry->getEncoder($encoding)->encodeExpr($expr);
    }

    /**
     * @param array $value
     *
     * @return int the checking result
     *             0  - the value is regular array
     *             1  - the value is an expression
     *             -1 - the value is an array with one item and its key starts with "\@"
     *                  which should be replaces with "@"
     */
    protected function checkArrayValue($value)
    {
        if (count($value) === 1) {
            reset($value);
            $k = key($value);
            if (is_string($k)) {
                $pos = strpos($k, '@');
                if ($pos === 0) {
                    // expression
                    return self::ARRAY_IS_EXPRESSION;
                } elseif ($pos === 1 && $k[0] === '\\') {
                    // the backslash (\) at the begin of the array key should be removed
                    return self::ARRAY_IS_EXPRESSION_STARTED_WITH_BACKSLASH;
                }
            }
        }

        // regular array
        return self::ARRAY_IS_REGULAR;
    }
}
