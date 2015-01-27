<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class BodyType extends AbstractContainerType
{
    const NAME = 'body';
    const OPTIONS_TAG_ATTRIBUTES = 'attributes';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([self::OPTIONS_TAG_ATTRIBUTES => []]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        foreach ($options[self::OPTIONS_TAG_ATTRIBUTES] as $attribute => $value) {
            if (!empty($value)) {
                $view->vars['attr'][$attribute] = $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
