<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class ExternalResourceType extends AbstractType
{
    const NAME = 'external_resource';

    /** @var array */
    protected static $attributes = [
        'href' => 'href',
        'rel'  => 'rel',
        'type' => 'type'
    ];

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $optionalOptions = array_values(self::$attributes);
        $resolver->setOptional($optionalOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        foreach (self::$attributes as $attr => $opt) {
            if (isset($options[$opt])) {
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
