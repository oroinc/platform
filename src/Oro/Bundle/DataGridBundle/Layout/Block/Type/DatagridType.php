<?php

namespace Oro\Bundle\DataGridBundle\Layout\Block\Type;

use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ImportLayoutManipulator;
use Oro\Component\Layout\Util\BlockUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DatagridType extends AbstractContainerType
{
    const NAME = 'datagrid';

    /** @var NameStrategyInterface */
    protected $nameStrategy;

    /** @var ManagerInterface */
    protected $manager;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /**
     * @param NameStrategyInterface         $nameStrategy
     * @param ManagerInterface              $manager
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        NameStrategyInterface $nameStrategy,
        ManagerInterface $manager,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->nameStrategy = $nameStrategy;
        $this->manager = $manager;
        $this->authorizationChecker = $authorizationChecker;
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
                'split_to_cells' => false,
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
                $rootId = $builder->getId();

                $headerRowId = $this->generateName([$rootId, 'header', 'row']);
                $this->addChild($builder, $rootId, $options, $headerRowId, 'datagrid_header_row');

                $rowId = $this->generateName([$rootId, 'row']);
                $this->addChild($builder, $rootId, $options, $rowId, 'datagrid_row');

                foreach ($columns as $columnName => $column) {
                    $headerCellId = $this->generateName([$rootId, 'header', 'cell', $columnName]);
                    $this->addChild(
                        $builder,
                        $headerRowId,
                        $options,
                        $headerCellId,
                        'datagrid_header_cell',
                        ['column_name' => $columnName]
                    );

                    $cellId = $this->generateName([$rootId, 'cell', $columnName]);
                    $this->addChild(
                        $builder,
                        $rowId,
                        $options,
                        $cellId,
                        'datagrid_cell',
                        ['column_name' => $columnName]
                    );

                    $cellValueId = $this->generateName([$rootId, 'cell', $columnName, 'value']);
                    $this->addChild(
                        $builder,
                        $cellId,
                        $options,
                        $cellValueId,
                        'datagrid_cell_value',
                        ['column_name' => $columnName]
                    );
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
            if ($aclResource && !$this->authorizationChecker->isGranted($aclResource)) {
                return false;
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * @param BlockBuilderInterface $builder
     * @param string                $rootId
     * @param Options               $rootOptions
     * @param string                $childId
     * @param string                $childType
     * @param array                 $childOptions
     *
     * @return DatagridType
     */
    private function addChild(
        BlockBuilderInterface $builder,
        $rootId,
        Options $rootOptions,
        $childId,
        $childType,
        array $childOptions = []
    ) {
        $options = $this->getChildOptions($rootOptions, $childType, $childOptions);
        $builder->getLayoutManipulator()->add($childId, $rootId, $childType, $options);

        return $this;
    }

    /**
     * @param Options $rootOptions
     * @param string  $childBlockType
     * @param array   $childOptions
     *
     * @return array
     */
    private function getChildOptions(Options $rootOptions, $childBlockType, array $childOptions = [])
    {
        $name = ImportLayoutManipulator::NAMESPACE_PLACEHOLDER . $childBlockType;

        $options = $rootOptions->toArray();
        if (array_key_exists('additional_block_prefixes', $options)) {
            foreach ($options['additional_block_prefixes'] as $prefix) {
                $parts = explode(ImportLayoutManipulator::NAMESPACE_PLACEHOLDER, $prefix);
                $lastPart = ImportLayoutManipulator::NAMESPACE_PLACEHOLDER . end($parts);
                $childOptions['additional_block_prefixes'][] = preg_replace('/' . $lastPart . '$/', $name, $prefix);
            }
        }

        return $childOptions;
    }

    /**
     * @param array $parts
     *
     * @return string
     */
    private function generateName(array $parts = [])
    {
        return implode('_', $parts);
    }
}
