<?php

namespace Oro\Bundle\UIBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\ButtonGroupType;

class PinnedButtonGroupType extends AbstractContainerType
{
    const NAME = 'pinned_button_group';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults(['group_name' => ''])
            ->setOptional(['more_button_attr'])
            ->setAllowedTypes(['more_button_attr' => 'array']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['group_name'] = $options['group_name'];
        if (!empty($options['more_button_attr'])) {
            $view->vars['more_button_attr'] = $options['more_button_attr'];
        }
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
        return ButtonGroupType::NAME;
    }
}
