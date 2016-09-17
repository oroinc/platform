<?php

namespace Oro\Bundle\DataGridBundle\Layout\Block\Type;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

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
            ->setDefaults([
                'grid_parameters' => [],
                'grid_render_parameters' => [],
                'split_to_cells' => false
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
        $view->vars['grid_name'] = $options['grid_name'];
        $view->vars['grid_parameters'] = $options['grid_parameters'];
        $view->vars['grid_render_parameters'] = $options['grid_render_parameters'];
        $view->vars['split_to_cells'] = $options['split_to_cells'];
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

    /**
     * @param string $gridName
     *
     * @return DatagridInterface
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
