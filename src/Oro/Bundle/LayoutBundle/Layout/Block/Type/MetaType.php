<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class MetaType extends AbstractType
{
    const NAME = 'meta';

    /** @var array */
    protected static $attributes = [
        'charset'    => 'charset',
        'content'    => 'content',
        'http-equiv' => 'http_equiv',
        'name'       => 'name'
    ];

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(array_values(self::$attributes));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        foreach (self::$attributes as $attr => $opt) {
            if (!empty($options[$opt])) {
                $view->vars['attr'][$attr] = $options[$opt];
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
