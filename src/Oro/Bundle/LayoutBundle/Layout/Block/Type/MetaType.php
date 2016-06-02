<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class MetaType extends AbstractType
{
    const NAME = 'meta';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['charset', 'content', 'http_equiv', 'name']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        if (!empty($options['charset'])) {
            $view->vars['attr']['charset'] = $options['charset'];
        }
        if (!empty($options['content'])) {
            $view->vars['attr']['content'] = $options['content'];
        }
        if (!empty($options['http_equiv'])) {
            $view->vars['attr']['http-equiv'] = $options['http_equiv'];
        }
        if (!empty($options['name'])) {
            $view->vars['attr']['name'] = $options['name'];
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
