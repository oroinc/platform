<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Extension;

use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\Options;
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
    public function normalizeOptions(Options $options, ContextInterface $context, DataAccessorInterface $data)
    {
        $values = $options->toArray();
        $this->processor->processExpressions($values, $context, null, true, null);
        $options->setMultiple($values);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block)
    {
        $context = $block->getContext();
        $data = $block->getData();

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
