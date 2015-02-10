<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class StyleType extends AbstractType
{
    const NAME = 'style';

    /** @var array */
    protected static $attributes = [
        'type'        => 'type',
        'src'         => 'src',
        'media'       => 'media',
        'scoped'      => 'scoped',
        'crossorigin' => 'crossorigin'
    ];

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $optionalOptions   = array_values(self::$attributes);
        $optionalOptions[] = 'content';
        $resolver->setOptional($optionalOptions);
        $resolver->setAllowedTypes(
            [
                'scoped' => 'bool'
            ]
        );
        $resolver->setAllowedValues(
            [
                'crossorigin' => ['anonymous', 'use-credentials']
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        foreach (self::$attributes as $attr => $opt) {
            if (isset($options[$opt])) {
                switch ($opt) {
                    case 'scoped':
                        if ($options[$opt]) {
                            $view->vars['attr'][$attr] = $attr;
                        }
                        break;
                    default:
                        $view->vars['attr'][$attr] = $options[$opt];
                }
            }
        }
        $view->vars['content'] = isset($options['content']) ? $options['content'] : '';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
