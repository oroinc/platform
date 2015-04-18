<?php

namespace Oro\Component\Layout\Block\Extension;

use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\OptionValueBag;
use Oro\Component\Layout\StringOptionValueBuilder;

/**
 * This extension normalizes 'class' attribute and allows to use [append/subtract/replace]Option methods
 * of the layout manipulator for this attribute.
 */
class ClassAttributeExtension extends AbstractBlockTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        $this->normalizeClassAttribute($view, 'attr');
        $this->normalizeClassAttribute($view, 'label_attr');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return BaseType::NAME;
    }

    /**
     * @param BlockView $view
     * @param string    $attrKey
     */
    protected function normalizeClassAttribute(BlockView $view, $attrKey)
    {
        if (!isset($view->vars[$attrKey])) {
            return;
        }

        if (isset($view->vars[$attrKey]['class']) || array_key_exists('class', $view->vars[$attrKey])) {
            $class = $view->vars[$attrKey]['class'];
            if ($class instanceof OptionValueBag) {
                $class = $class->buildValue(new StringOptionValueBuilder());
            }
            if (!empty($class)) {
                $view->vars[$attrKey]['class'] = $class;
            } else {
                unset($view->vars[$attrKey]['class']);
            }
        }
    }
}
