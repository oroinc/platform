<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class LinkType extends AbstractType
{
    const NAME = 'link';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(['text'])
            ->setOptional(['path', 'route_name', 'route_parameters'])
            ->setDefaults(
                [
                    'text_parameters' => [],
                    'translatable'    => true
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        if (!empty($options['path'])) {
            $view->vars['path'] = $options['path'];
        } elseif (!empty($options['route_name'])) {
            $view->vars['route_name']       = $options['route_name'];
            $view->vars['route_parameters'] = isset($options['route_parameters'])
                ? $options['route_parameters']
                : [];
        } else {
            throw new MissingOptionsException('Either "path" or "route_name" must be set.');
        }

        $view->vars['text']            = $options['text'];
        $view->vars['text_parameters'] = $options['text_parameters'];
        $view->vars['translatable']    = $options['translatable'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
