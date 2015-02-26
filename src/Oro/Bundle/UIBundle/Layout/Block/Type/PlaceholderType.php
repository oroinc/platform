<?php

namespace Oro\Bundle\UIBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

/**
 * We have to use this approach to keep backward compatibility with old pages.
 * It is not recommended to use this block type in new layouts, because placeholders
 * generate HTML based on data and it prevents to effectively layout caching.
 */
class PlaceholderType extends AbstractType
{
    const NAME = 'placeholder';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(['placeholder_name'])
            ->setOptional(['placeholder_parameters']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['placeholder_name']       = $options['placeholder_name'];
        $view->vars['placeholder_parameters'] = !empty($options['placeholder_parameters'])
            ? $options['placeholder_parameters'] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
