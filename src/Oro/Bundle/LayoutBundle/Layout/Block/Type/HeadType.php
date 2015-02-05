<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockTypeHelperInterface;
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
        $typeHelper = $block->getTypeHelper();
        ArrayUtils::sortBy(
            $view->children,
            false,
            function (BlockView $childView) use ($typeHelper) {
                return $this->getChildPriority($childView, $typeHelper);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param BlockView                $childView
     * @param BlockTypeHelperInterface $typeHelper
     *
     * @return int
     */
    protected function getChildPriority(BlockView $childView, BlockTypeHelperInterface $typeHelper)
    {
        $type = $childView->vars['block_type'];
        if ($typeHelper->isInstanceOf($type, MetaType::NAME)) {
            return 10;
        }
        if ($typeHelper->isInstanceOf($type, StyleType::NAME)) {
            return 20;
        }
        if ($typeHelper->isInstanceOf($type, ScriptType::NAME)) {
            return 30;
        }

        return 255;
    }
}
