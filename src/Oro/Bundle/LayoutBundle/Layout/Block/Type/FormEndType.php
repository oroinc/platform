<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class FormEndType extends AbstractFormType
{
    const NAME = 'form_end';

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $formAccessor = $this->getFormAccessor($block->getContext(), $options);

        $view->vars['form'] = $formAccessor->getView();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
