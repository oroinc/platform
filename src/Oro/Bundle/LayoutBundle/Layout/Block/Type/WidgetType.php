<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class WidgetType extends AbstractContainerType
{
    const NAME = 'widget';

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['wid'] = $this->getUniqueIdentifier();
    }

    /**
     * @return string
     */
    protected function getUniqueIdentifier()
    {
        return str_replace('.', '-', uniqid('', true));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
