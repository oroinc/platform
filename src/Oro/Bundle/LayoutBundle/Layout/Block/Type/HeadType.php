<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Util\ArrayUtils;

class HeadType extends AbstractContainerType
{
    const NAME = 'head';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'title' => ''
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['title'] = $options['title'];
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        ArrayUtils::sortBy($view->children, false, [$this, 'getChildPriority']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param BlockView $childView
     *
     * @return int
     */
    public function getChildPriority(BlockView $childView)
    {
        if ($childView->isInstanceOf(MetaType::NAME)) {
            return 10;
        }
        if ($childView->isInstanceOf(StyleType::NAME)) {
            return 20;
        }
        if ($childView->isInstanceOf(ScriptType::NAME)) {
            return 30;
        }

        return 255;
    }
}
