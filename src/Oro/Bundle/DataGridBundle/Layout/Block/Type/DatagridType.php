<?php

namespace Oro\Bundle\DataGridBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

use Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface;

class DatagridType extends AbstractType
{
    const NAME = 'datagrid';

    /** @var NameStrategyInterface */
    protected $nameStrategy;

    /**
     * @param NameStrategyInterface $nameStrategy
     */
    public function __construct(NameStrategyInterface $nameStrategy)
    {
        $this->nameStrategy = $nameStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(['grid_name'])
            ->setOptional(['grid_scope'])
            ->setDefaults(['grid_parameters' => []])
            ->setDefaults(['grid_render_parameters' => []])
            ->setAllowedTypes(
                [
                    'grid_name' => 'string',
                    'grid_scope' => 'string',
                    'grid_parameters' => 'array',
                    'grid_render_parameters' => 'array'
                ]
            )
            ->setNormalizers(
                [
                    'grid_parameters' => function (Options $options, $value) {
                        return array_merge(['enableFullScreenLayout' => true], $value);
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
        $view->vars['grid_parameters'] = $options['grid_parameters'];
        $view->vars['grid_render_parameters'] = $options['grid_render_parameters'];
        if (!empty($options['grid_scope'])) {
            $view->vars['grid_scope']     = $options['grid_scope'];
            $view->vars['grid_full_name'] = $this->nameStrategy->buildGridFullName(
                $view->vars['grid_name'],
                $view->vars['grid_scope']
            );
        } else {
            $view->vars['grid_full_name'] = $view->vars['grid_name'];
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
