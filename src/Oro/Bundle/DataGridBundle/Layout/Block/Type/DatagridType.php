<?php

namespace Oro\Bundle\DataGridBundle\Layout\Block\Type;

use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Util\BlockUtils;

class DatagridType extends AbstractContainerType
{
    const NAME = 'datagrid';

    /** @var NameStrategyInterface */
    protected $nameStrategy;

    /** @var ManagerInterface */
    protected $manager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param NameStrategyInterface $nameStrategy
     * @param ManagerInterface $manager
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        NameStrategyInterface $nameStrategy,
        ManagerInterface $manager,
        SecurityFacade $securityFacade
    ) {
        $this->nameStrategy = $nameStrategy;
        $this->manager = $manager;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['grid_name'])
            ->setDefined(['grid_scope'])
            ->setDefined('server_side_render')
            ->setDefaults([
                'grid_parameters' => [],
                'grid_render_parameters' => [],
                'split_to_cells' => false,
                'server_side_render' => false,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildBlock(BlockBuilderInterface $builder, Options $options)
    {
        if ($options['split_to_cells']) {
            $columns = $this->getGridColumns($options['grid_name']);
            if ($columns) {
                $id = $builder->getId();

                $headerRowId = $this->generateName([$id, 'header', 'row']);
                $builder->getLayoutManipulator()->add($headerRowId, $id, 'datagrid_header_row');

                $rowId = $this->generateName([$id, 'row']);
                $builder->getLayoutManipulator()->add($rowId, $id, 'datagrid_row');

                foreach ($columns as $columnName => $column) {
                    $headerCellId = $this->generateName([$id, 'header', 'cell', $columnName]);
                    $builder->getLayoutManipulator()
                        ->add($headerCellId, $headerRowId, 'datagrid_header_cell', ['column_name' => $columnName]);

                    $cellId = $this->generateName([$id, 'cell', $columnName]);
                    $builder->getLayoutManipulator()
                        ->add($cellId, $rowId, 'datagrid_cell', ['column_name' => $columnName]);

                    $cellValueId = $this->generateName([$id, 'cell', $columnName, 'value']);
                    $builder->getLayoutManipulator()
                        ->add($cellValueId, $cellId, 'datagrid_cell_value', ['column_name' => $columnName]);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        BlockUtils::setViewVarsFromOptions($view, $options, [
            'grid_name',
            'grid_parameters',
            'grid_render_parameters',
            'server_side_render'
        ]);

        $view->vars['split_to_cells'] = $options['split_to_cells'];
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
    public function finishView(BlockView $view, BlockInterface $block)
    {
        if ($view->vars['server_side_render']) {
            BlockUtils::registerPlugin($view, 'server_render_' . $view->vars['block_type']);

            $namespace = substr($block->getId(), 0, strrpos($block->getId(), '_'));

            foreach ($view->children as $childView) {
                $this->addServerRenderBlockPrefix($childView, $namespace);
            }
        }
    }

    /**
     * @param BlockView $view
     * @param string $namespace
     */
    private function addServerRenderBlockPrefix(BlockView $view, $namespace)
    {
        $suffix = substr($view->vars['unique_block_prefix'], strlen($namespace) + 2);
        $view->vars['additional_block_prefixes'][] = 'server_render_' . $view->vars['block_type'];
        $view->vars['additional_block_prefixes'][] = 'server_render_' . $suffix;
        $view->vars['additional_block_prefixes'] = array_unique($view->vars['additional_block_prefixes']);

        foreach ($view->children as $childView) {
            $this->addServerRenderBlockPrefix($childView, $namespace);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param string $gridName
     *
     * @return array
     */
    private function getGridColumns($gridName)
    {
        if ($this->isAclGrantedForGridName($gridName)) {
            return $this->manager->getConfigurationForGrid($gridName)->offsetGet('columns');
        }

        return null;
    }

    /**
     * @param string $gridName
     *
     * @return bool
     */
    private function isAclGrantedForGridName($gridName)
    {
        $gridConfig = $this->manager->getConfigurationForGrid($gridName);

        if ($gridConfig) {
            $aclResource = $gridConfig->getAclResource();
            if ($aclResource && !$this->securityFacade->isGranted($aclResource)) {
                return false;
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $parts
     *
     * @return string
     */
    private function generateName($parts = [])
    {
        return implode('_', $parts);
    }
}
