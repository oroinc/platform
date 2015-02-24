<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Component\ConfigExpression\AssemblerInterface;
use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderRegistryInterface;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ConfigExpressionCompilerPass;

/**
 * Allows to use expressions (see ConfigExpression component) in block type options and attributes.
 */
class ConfigExpressionExtension extends AbstractBlockTypeExtension implements ContextConfiguratorInterface
{
    const MAX_EXPRESSION_NESTING_LEVEL = 5;

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
    public function configureContext(ContextInterface $context)
    {
        $context->getDataResolver()
            ->setDefaults(['expressions.evaluate' => true])
            ->setOptional(['expressions.encoding']);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        $context  = $block->getContext();
        $evaluate = $context->get('expressions.evaluate');
        $encoding = $context->getOr('expressions.encoding');
        if ($evaluate || $encoding !== null) {
            $view->vars = $this->processExpressions(
                $view->vars,
                $view,
                $context,
                $block->getData(),
                $evaluate,
                $encoding,
                true
            );
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
     * @param array                         $values
     * @param BlockView                     $view
     * @param ContextInterface              $context
     * @param DataProviderRegistryInterface $data
     * @param bool                          $evaluate
     * @param string                        $encoding
     * @param bool                          $isVars
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function processExpressions(
        array &$values,
        BlockView $view,
        ContextInterface $context,
        DataProviderRegistryInterface $data,
        $evaluate,
        $encoding,
        $isVars = false
    ) {
        $removeBackslashKeys = [];
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                if ($isVars && ($key === 'attr' || $key === 'label_attr')) {
                    // process block and block label attributes
                    if (!empty($value)) {
                        $values[$key] = $this->processExpressions(
                            $value,
                            $view,
                            $context,
                            $data,
                            $evaluate,
                            $encoding
                        );
                    }
                    continue;
                } elseif (count($value) === 1) {
                    // try to assemble an expression
                    reset($value);
                    $k = key($value);
                    if (is_string($k)) {
                        $pos = strpos($k, '@');
                        if ($pos === 0) {
                            // do an expression assembling
                            $value        = $this->expressionAssembler->assemble($value);
                            $values[$key] = $value;
                        } elseif ($pos === 1 && $k[0] === '\\') {
                            // the backslash (\) at the begin of the array key should be removed
                            $removeBackslashKeys[] = $key;
                            continue;
                        }
                    }
                }
            }
            if ($value instanceof ExpressionInterface && $values[$key] instanceof ExpressionInterface) {
                if ($evaluate) {
                    $values[$key] = $value->evaluate(
                        $this->getEvaluateExpressionContext($view, $context, $data, $isVars ? $key : null)
                    );
                } else {
                    $values[$key] = $this->encodeExpression($value, $encoding);
                }
            }
        }
        // remove the backslash (\) at the begin of the array key
        foreach ($removeBackslashKeys as $key) {
            $value        = $values[$key];
            $v            = reset($value);
            $values[$key] = [substr(key($value), 1) => $v];
        }

        return $values;
    }

    /**
     * @param BlockView                     $view
     * @param ContextInterface              $context
     * @param DataProviderRegistryInterface $data
     * @param string                        $excludeKey
     * @param int                           $nestingLevel
     *
     * @return array
     */
    protected function getEvaluateExpressionContext(
        BlockView $view,
        ContextInterface $context,
        DataProviderRegistryInterface $data,
        $excludeKey = null,
        $nestingLevel = 0
    ) {
        $result = ['context' => $context, 'data' => $data];
        foreach ($view->vars as $key => $value) {
            if (strpos($key, 'data-') === 0) {
                if ($excludeKey !== null && $excludeKey === $key) {
                    // skip a variable for which an expression is calculated
                    continue;
                }
                // check if a data value is an expression which is not evaluated yet
                if (is_array($value) && count($value) === 1) {
                    reset($value);
                    $k = key($value);
                    if (is_string($k) && strpos($k, '@') === 0) {
                        // do an expression assembling
                        $value            = $this->expressionAssembler->assemble($value);
                        $view->vars[$key] = $value;
                    }
                }
                if ($value instanceof ExpressionInterface) {
                    // do simplified check for circular references
                    if ($nestingLevel > static::MAX_EXPRESSION_NESTING_LEVEL) {
                        throw new \RuntimeException(
                            sprintf(
                                'Circular reference in an expression for variable "%s" of block "%s". '
                                . 'Max nesting level is %s.',
                                $key,
                                $view->vars['id'],
                                static::MAX_EXPRESSION_NESTING_LEVEL
                            )
                        );
                    }
                    $value            = $value->evaluate(
                        $this->getEvaluateExpressionContext($view, $context, $data, $key, $nestingLevel + 1)
                    );
                    $view->vars[$key] = $value;
                }
                $result[substr($key, 5)] = $value;
            }
        }

        return $result;
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
}
