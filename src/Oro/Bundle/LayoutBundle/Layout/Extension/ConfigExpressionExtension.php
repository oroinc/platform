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

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ConfigExpressionCompilerPass;

class ConfigExpressionExtension extends AbstractBlockTypeExtension implements ContextConfiguratorInterface
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
            $view->vars = $this->processExpressions($view->vars, $context, $evaluate, $encoding, true);
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
     * @param array            $values
     * @param ContextInterface $context
     * @param bool             $evaluate
     * @param string           $encoding
     * @param bool             $isVars
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function processExpressions(
        array $values,
        ContextInterface $context,
        $evaluate,
        $encoding,
        $isVars = false
    ) {
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                if ($isVars && ($key === 'attr' || $key === 'label_attr')) {
                    // process block and block label attributes
                    if (!empty($value)) {
                        $values[$key] = $this->processExpressions($value, $context, $evaluate, $encoding);
                    }
                    continue;
                } elseif (count($value) === 1) {
                    // try to assemble an expression
                    $v = reset($value);
                    $k = key($value);
                    if (is_string($k)) {
                        $pos = strpos($k, '@');
                        if ($pos === 0) {
                            // do an expression assembling
                            $value = $this->expressionAssembler->assemble($value);
                        } elseif ($pos === 1 && $k[0] === '\\') {
                            // remove the backslash (\) at the begin of the array key
                            $values[$key] = [substr($k, 1) => $v];
                            continue;
                        }
                    }
                }
            }
            if ($value instanceof ExpressionInterface) {
                $values[$key] = $evaluate
                    ? $value->evaluate(['context' => $context])
                    : $this->encodeExpression($value, $encoding);
            }
        }

        return $values;
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
