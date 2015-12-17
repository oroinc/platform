<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Extension;

use Oro\Component\ConfigExpression\AssemblerInterface;
use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataAccessorInterface;
use Oro\Component\Layout\OptionValueBag;

use Oro\Bundle\LayoutBundle\Layout\Encoder\ConfigExpressionEncoderRegistry;

/**
 * Allows to use expressions (see ConfigExpression component) in block type options and attributes.
 */
class ConfigExpressionExtension extends AbstractBlockTypeExtension
{
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
     * {@inheritdoc}
     */
    public function normalizeOptions($name, array &$options, ContextInterface $context, DataAccessorInterface $data)
    {
        if ($this->shouldBeProcessed($context, false)) {
            $evaluate = $context->getOr('expressions_evaluate');
            $encoding = $context->getOr('expressions_encoding');

            $this->processExpressions($options, $context, $data, $evaluate, $encoding);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        $context = $block->getContext();

        if ($this->shouldBeProcessed($context, true)) {
            $evaluate = $context->getOr('expressions_evaluate');
            $encoding = $context->getOr('expressions_encoding');

            $data = $block->getData();
            $this->processExpressions($options, $context, $data, $evaluate, $encoding);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return BaseType::NAME;
    }

    /**
     * @param ContextInterface $context
     * @param bool $isDeferred
     * @return bool
     */
    protected function shouldBeProcessed(ContextInterface $context, $isDeferred)
    {
        $evaluateDeferred = $context->getOr('expressions_evaluate_deferred');
        if ($evaluateDeferred === $isDeferred) {
            $evaluate = $context->getOr('expressions_evaluate');
            $encoding = $context->getOr('expressions_encoding');

            return $evaluate || $encoding !== null;
        }
        return false;
    }

    /**
     * @param array                 $values
     * @param ContextInterface      $context
     * @param DataAccessorInterface $data
     * @param bool                  $evaluate
     * @param string                $encoding
     */
    protected function processExpressions(
        array &$values,
        ContextInterface $context,
        DataAccessorInterface $data,
        $evaluate,
        $encoding
    ) {
        foreach ($values as $key => &$value) {
            if (is_array($value)) {
                if (!empty($value)) {
                    switch ($this->checkArrayValue($value)) {
                        case 0:
                            $this->processExpressions($value, $context, $data, $evaluate, $encoding);
                            break;
                        case 1:
                            $value = $this->processExpression(
                                $this->expressionAssembler->assemble($value),
                                $context,
                                $data,
                                $evaluate,
                                $encoding
                            );
                            break;
                        case -1:
                            // the backslash (\) at the begin of the array key should be removed
                            $value = [substr(key($value), 1) => reset($value)];
                            break;
                    }
                }
            } elseif ($value instanceof ExpressionInterface) {
                $value = $this->processExpression($value, $context, $data, $evaluate, $encoding);
            } elseif ($value instanceof OptionValueBag) {
                foreach ($value->all() as $action) {
                    $args = $action->getArguments();
                    $this->processExpressions($args, $context, $data, $evaluate, $encoding);
                    foreach ($args as $index => $arg) {
                        $action->setArgument($index, $arg);
                    }
                }
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
     *             -1 - the value is an array with one items and its key starts with "\@"
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
                    return 1;
                } elseif ($pos === 1 && $k[0] === '\\') {
                    // the backslash (\) at the begin of the array key should be removed
                    return -1;
                }
            }
        }

        // regular array
        return 0;
    }
}
