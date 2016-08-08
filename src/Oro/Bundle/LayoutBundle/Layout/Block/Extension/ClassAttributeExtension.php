<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Extension;

use Oro\Component\Layout\Block\Extension\ClassAttributeExtension as BaseClassAttributeExtension;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\OptionValueBag;

use Oro\Bundle\LayoutBundle\Layout\Encoder\ExpressionEncoderRegistry;

/**
 * This extension normalizes 'class' attribute and allows to use [append/subtract/replace]Option methods
 * of the layout manipulator for this attribute.
 * This extension allows to use config expressions 'class' attribute.
 */
class ClassAttributeExtension extends BaseClassAttributeExtension
{
    /** @var ExpressionEncoderRegistry */
    protected $encoderRegistry;

    /**
     * @param ExpressionEncoderRegistry $encoderRegistry
     */
    public function __construct(ExpressionEncoderRegistry $encoderRegistry)
    {
        $this->encoderRegistry = $encoderRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        $context = $block->getContext();
        if ($context->getOr('expressions_evaluate')) {
            parent::finishView($view, $block, $options);
        } else {
            $encoding = $context->getOr('expressions_encoding');
            if ($encoding !== null) {
                $this->normalizeClassAttributeWithEncoding($view, 'attr', $encoding);
                $this->normalizeClassAttributeWithEncoding($view, 'label_attr', $encoding);
            }
        }
    }

    /**
     * @param BlockView $view
     * @param string    $attrKey
     * @param string    $encoding
     */
    protected function normalizeClassAttributeWithEncoding(BlockView $view, $attrKey, $encoding)
    {
        if (!isset($view->vars[$attrKey])) {
            return;
        }

        if (isset($view->vars[$attrKey]['class']) || array_key_exists('class', $view->vars[$attrKey])) {
            $class = $view->vars[$attrKey]['class'];
            if ($class instanceof OptionValueBag) {
                $class = $this->encoderRegistry->getEncoder($encoding)->encodeActions($class->all());
            }
            if (!empty($class)) {
                $view->vars[$attrKey]['class'] = $class;
            } else {
                unset($view->vars[$attrKey]['class']);
            }
        }
    }
}
