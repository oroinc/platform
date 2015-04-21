<?php

namespace Oro\Bundle\UIBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockTypeHelperInterface;
use Oro\Component\Layout\BlockView;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\LinkType;
use Oro\Bundle\LayoutBundle\Layout\Block\Type\ListType;

class BreadcrumbListType extends AbstractContainerType
{
    const NAME = 'breadcrumbs';

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        $this->askChildrenToAddPageParameters($view, $block->getTypeHelper());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ListType::NAME;
    }

    /**
     * Iterates through all children and ask them to add parameters from current request to theirs urls
     *
     * @param BlockView                $view
     * @param BlockTypeHelperInterface $typeHelper
     */
    protected function askChildrenToAddPageParameters(BlockView $view, BlockTypeHelperInterface $typeHelper)
    {
        foreach ($view->children as $child) {
            if ($typeHelper->isInstanceOf($child->vars['block_type'], LinkType::NAME)) {
                $child->vars['with_page_parameters'] = true;
            } elseif (!empty($child->children)) {
                $this->askChildrenToAddPageParameters($child, $typeHelper);
            }
        }
    }
}
