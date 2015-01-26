<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class ScriptType extends AbstractType
{
    const NAME = 'script';

    /** @var array */
    protected static $attributes = [
        'type'        => 'type',
        'src'         => 'src',
        'async'       => 'async',
        'defer'       => 'defer',
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
                'async' => 'bool',
                'defer' => 'bool',
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
            if (!empty($options[$opt])) {
                switch ($opt) {
                    case 'async':
                    case 'defer':
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
