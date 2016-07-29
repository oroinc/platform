<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Extension;

use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataAccessorInterface;

use Oro\Bundle\LayoutBundle\Layout\Processor\ExpressionProcessor;

/**
 * Allows to use expressions (see ConfigExpression component) in block type options and attributes.
 */
class ExpressionExtension extends AbstractBlockTypeExtension
{
    /** @var ExpressionProcessor */
    protected $processor;

    /**
     * @param ExpressionProcessor $processor
     */
    public function __construct(
        ExpressionProcessor $processor
    ) {
        $this->processor = $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeOptions(array &$options, ContextInterface $context, DataAccessorInterface $data)
    {
        if (false !== $context->getOr('expressions_evaluate_deferred')) {
            return;
        }
        $evaluate = $context->getOr('expressions_evaluate');
        $encoding = $context->getOr('expressions_encoding');

        $this->processor->processExpressions($options, $context, $data, $evaluate, $encoding);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        $context = $block->getContext();
        $data = $block->getData();

        if (true !== $context->getOr('expressions_evaluate_deferred')) {
            return;
        }
        $evaluate = $context->getOr('expressions_evaluate');
        $encoding = $context->getOr('expressions_encoding');

        $this->processor->processExpressions($view->vars, $context, $data, $evaluate, $encoding);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return BaseType::NAME;
    }
}
