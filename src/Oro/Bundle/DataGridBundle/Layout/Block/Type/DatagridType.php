<?php

namespace Oro\Bundle\DataGridBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\Options;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['grid_name'])
            ->setDefined(['grid_scope'])
            ->setDefaults([
                'grid_parameters' => [],
                'grid_render_parameters' => []
            ]);
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
