<?php

namespace Oro\Bundle\UIBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class DatagridType extends AbstractType
{
    const NAME = 'datagrid';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(['grid_name'])
            ->setOptional(['grid_scope'])
            ->setDefaults(['params' => []]);

        $resolver->setAllowedTypes(
            [
                'grid_name'  => 'string',
                'grid_scope' => 'string',
                'params'     => 'array',
            ]
        );
        $resolver->setNormalizers(
            [
                'params' => function (Options $options, $params) {
                    return array_merge(['enableFullScreenLayout' => true], $params);
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['grid_name'] = $options['grid_name'];
        $view->vars['params']    = $options['params'];
        if (!empty($options['grid_scope'])) {
            $view->vars['grid_scope'] = $options['grid_scope'];
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
