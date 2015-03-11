<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Component\ConfigExpression\AssemblerInterface;
use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataAccessorInterface;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ConfigExpressionCompilerPass;
use Oro\Bundle\LayoutBundle\Layout\Encoder\ConfigExpressionEncoderInterface;

/**
 * Allows to use expressions (see ConfigExpression component) in block type options and attributes.
 */
class ConfigExpressionExtension extends AbstractBlockTypeExtension
{
    /** @var AssemblerInterface */
    protected $expressionAssembler;

    /** @var ContainerInterface */
    protected $container;

    /** @var string[] */
    protected $encoders;

    /**
     * @param AssemblerInterface $expressionAssembler
     * @param ContainerInterface $container
     * @param string[]           $encoders
     */
    public function __construct(
        AssemblerInterface $expressionAssembler,
        ContainerInterface $container,
        array $encoders
    ) {
        $this->expressionAssembler = $expressionAssembler;
        $this->container           = $container;
        $this->encoders            = $encoders;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        $context  = $block->getContext();
        $evaluate = $context->getOr('expressions_evaluate');
        $encoding = $context->getOr('expressions_encoding');
        if ($evaluate || $encoding !== null) {
            $this->processExpressions($view->vars, $context, $block->getData(), $evaluate, $encoding);
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
            : $this->encodeExpression($expr, $encoding);
    }

    /**
     * @param ExpressionInterface $expr
     * @param string              $format
     *
     * @return string
     *
     * @throws \RuntimeException if the appropriate encoder does not exist
     */
    protected function encodeExpression(ExpressionInterface $expr, $format)
    {
        if (!isset($this->encoders[$format])) {
            throw new \RuntimeException(
                sprintf(
                    'The expression encoder for "%s" formatting was not found. '
                    . 'Check that the appropriate encoder service is registered in '
                    . 'the container and marked by tag "%s".',
                    $format,
                    ConfigExpressionCompilerPass::EXPRESSION_ENCODER_TAG
                )
            );
        }

        /** @var ConfigExpressionEncoderInterface $encoder */
        $encoder = $this->container->get($this->encoders[$format]);

        return $encoder->encode($expr);
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
