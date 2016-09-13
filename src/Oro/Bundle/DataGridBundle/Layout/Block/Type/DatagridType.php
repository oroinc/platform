<?php

namespace Oro\Bundle\DataGridBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\Block\Type\Options;
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
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        $view->vars['grid_name'] = $options->get('grid_name', false);
        $view->vars['grid_parameters'] = $options->get('grid_parameters', false);
        $view->vars['grid_render_parameters'] = $options->get('grid_render_parameters', false);
        if (!empty($options['grid_scope'])) {
            $view->vars['grid_scope']     = $options->get('grid_scope', false);
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
