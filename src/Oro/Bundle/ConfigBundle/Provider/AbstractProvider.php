<?php

namespace Oro\Bundle\ConfigBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ApiTree\SectionDefinition;
use Oro\Bundle\ConfigBundle\Config\ApiTree\VariableDefinition;
use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\DataTransformerInterface;
use Oro\Bundle\ConfigBundle\Config\Tree\FieldNodeDefinition;
use Oro\Bundle\ConfigBundle\Config\Tree\GroupNodeDefinition;
use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsFormOptionsEvent;
use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;
use Oro\Bundle\ConfigBundle\Form\Type\FormFieldType;
use Oro\Bundle\ConfigBundle\Form\Type\FormType;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The base realization a service that provides configuration of a system configuration form.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractProvider implements ProviderInterface
{
    protected const CORRECT_FIELDS_NESTING_LEVEL = 5;
    protected const CORRECT_MENU_NESTING_LEVEL = 3;

    protected ConfigBag $configBag;
    protected TranslatorInterface $translator;
    protected FormFactoryInterface $formFactory;
    protected FormRegistryInterface $formRegistry;
    protected AuthorizationCheckerInterface $authorizationChecker;
    protected ChainSearchProvider $searchProvider;
    protected FeatureChecker $featureChecker;
    protected EventDispatcherInterface $eventDispatcher;
    protected array $processedTrees = [];
    protected array $processedJsTrees = [];
    protected array $processedSubTrees = [];

    public function __construct(
        ConfigBag $configBag,
        TranslatorInterface $translator,
        FormFactoryInterface $factory,
        FormRegistryInterface $formRegistry,
        AuthorizationCheckerInterface $authorizationChecker,
        ChainSearchProvider $searchProvider,
        FeatureChecker $featureChecker,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->configBag = $configBag;
        $this->translator = $translator;
        $this->formFactory = $factory;
        $this->formRegistry = $formRegistry;
        $this->authorizationChecker = $authorizationChecker;
        $this->searchProvider = $searchProvider;
        $this->featureChecker = $featureChecker;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Gets the name of the configuration tree section.
     */
    abstract protected function getTreeName(): string;

    /**
     * Gets the label for "Use Default" checkbox.
     */
    abstract protected function getParentCheckboxLabel(): string;

    /**
     * {@inheritdoc}
     */
    public function getTree(): GroupNodeDefinition
    {
        return $this->getTreeData($this->getTreeName(), self::CORRECT_FIELDS_NESTING_LEVEL);
    }

    /**
     * {@inheritdoc}
     */
    public function getJsTree(): array
    {
        return $this->getJsTreeData($this->getTreeName(), self::CORRECT_MENU_NESTING_LEVEL);
    }

    /**
     * {@inheritdoc}
     */
    public function getApiTree(?string $path = null): ?SectionDefinition
    {
        $sections = empty($path) ? [] : explode('/', $path);
        array_unshift($sections, ProcessorDecorator::API_TREE_ROOT);

        $tree = $this->configBag->getConfig();
        $rootSectionName = null;
        foreach ($sections as $section) {
            if (!isset($tree[$section])) {
                throw new ItemNotFoundException(sprintf('Config API section "%s" is not defined.', $path));
            }
            $tree            = & $tree[$section];
            $rootSectionName = $section;
        }

        return $this->buildApiTree(
            $rootSectionName === ProcessorDecorator::API_TREE_ROOT ? '' : $rootSectionName,
            $tree
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSubTree(string $subTreeName): GroupNodeDefinition
    {
        if (!isset($this->processedSubTrees[$subTreeName])) {
            $treeData = $this->getTree();
            $subtree = TreeUtils::findNodeByName($treeData, $subTreeName);
            if ($subtree === null) {
                throw new ItemNotFoundException(sprintf('Subtree "%s" not found', $subTreeName));
            }

            $this->processedSubTrees[$subTreeName] = $subtree;
        }

        return $this->processedSubTrees[$subTreeName];
    }

    /**
     * @param string $treeName
     * @param int    $correctFieldsLevel
     *
     * @throws ItemNotFoundException
     * @return GroupNodeDefinition
     */
    protected function getTreeData($treeName, $correctFieldsLevel)
    {
        if (!isset($this->processedTrees[$treeName])) {
            $treeRoot = $this->configBag->getTreeRoot($treeName);
            if ($treeRoot === false) {
                throw new ItemNotFoundException(sprintf('Tree "%s" is not defined.', $treeName));
            }

            $definition = $this->filterDisabledNodes($treeRoot);
            $data = $this->buildGroupNode($definition, $correctFieldsLevel);
            $tree = new GroupNodeDefinition($treeName, $definition, $data);
            $this->processedTrees[$tree->getName()] = $tree;
        }

        return $this->processedTrees[$treeName];
    }

    /**
     * @param string $treeName
     * @param int $correctMenuLevel
     *
     * @throws ItemNotFoundException
     * @return array
     */
    protected function getJsTreeData($treeName, $correctMenuLevel)
    {
        if (!isset($this->processedJsTrees[$treeName])) {
            $treeRoot = $this->configBag->getTreeRoot($treeName);
            if ($treeRoot === false) {
                throw new ItemNotFoundException(sprintf('Tree "%s" is not defined.', $treeName));
            }

            $nodes = $this->filterDisabledNodes($treeRoot);
            $nodes = $this->removeEmptyNodes($nodes);
            $this->processedJsTrees[$treeName] = $this->buildJsTree(
                $nodes,
                $correctMenuLevel
            );
        }

        return $this->processedJsTrees[$treeName];
    }

    protected function filterDisabledNodes(array $definition): array
    {
        foreach ($definition as $key => $definitionRow) {
            if (\is_string($definitionRow)
                && !$this->featureChecker->isResourceEnabled($definitionRow, 'configuration')
            ) {
                unset($definition[$key]);
            } elseif (\is_array($definitionRow) && \array_key_exists('children', $definitionRow)) {
                if ($this->featureChecker->isResourceEnabled($key, 'configuration')) {
                    $definition[$key]['children'] = $this->filterDisabledNodes($definitionRow['children']);
                } else {
                    unset($definition[$key]);
                }
            }
        }

        return $definition;
    }

    private function removeEmptyNodes(array $definition): array
    {
        if (isset($definition['priority'], $definition['children'])) {
            $definition = [
                'children' => $definition['children'],
                'priority' => $definition['priority']
            ];
        }

        foreach ($definition as $key => $value) {
            if (is_array($value)) {
                $definition[$key] = $this->removeEmptyNodes($value);
            }

            if (empty($definition[$key]) || ('priority' === $key && 1 === count($definition))) {
                unset($definition[$key]);
            }
        }

        return $definition;
    }

    /**
     * Builds group node, called recursively
     *
     * @param array $nodes
     * @param int   $correctFieldsLevel fields should be placed on the same levels that comes from view
     * @param int   $level              current level
     *
     * @throws ItemNotFoundException
     * @throws \Exception
     * @return array
     */
    protected function buildGroupNode($nodes, $correctFieldsLevel, $level = 0)
    {
        $level++;
        foreach ($nodes as $name => $node) {
            if (is_array($node) && isset($node['children'])) {
                $group = $this->configBag->getGroupsNode($name);
                if ($group === false) {
                    throw new ItemNotFoundException(sprintf('Group "%s" is not defined.', $name));
                }

                $data  = $this->buildGroupNode($node['children'], $correctFieldsLevel, $level);
                $node  = new GroupNodeDefinition($name, array_merge($group, $nodes[$name]), $data);
                $node->setLevel($level);

                $nodes[$node->getName()] = $node;
            } else {
                if ($level !== $correctFieldsLevel) {
                    $field = is_string($node) ? $node : $name;
                    throw new \Exception(
                        sprintf('Field "%s" will not be ever rendered. Please check nesting level', $field)
                    );
                }
                $nodes[$name] = $this->buildFieldNode($node);
            }
        }

        return $nodes;
    }

    /**
     * @param array $nodes
     * @param int $correctMenuLevel
     * @param int $level
     * @param string $parentName
     * @param array $groupedData
     * @return array
     */
    protected function buildJsTree($nodes, $correctMenuLevel, $level = 0, $parentName = '#', array $groupedData = [])
    {
        $nodes = $this->sortChildrenByPriority($nodes);

        $level++;
        foreach ($nodes as $name => $node) {
            if (is_array($node) && isset($node['children']) && $correctMenuLevel > $level) {
                $groupedData = $this->buildJsTree(
                    $node['children'],
                    $correctMenuLevel,
                    $level,
                    $name,
                    $groupedData
                );

                $groupedData[] = $this->buildJsTreeItem($name, $parentName, $node);
            } else {
                $groupedData[] = $this->buildJsTreeItemWithSearchData($name, $parentName, $node);
            }
        }

        return $groupedData;
    }

    /**
     * @param string $name
     * @param string $parentName
     * @param array $node
     * @return array
     */
    protected function buildJsTreeItem($name, $parentName, array $node)
    {
        $group = $this->configBag->getGroupsNode($name);

        if ($group === false) {
            throw new ItemNotFoundException(sprintf('Group "%s" is not defined.', $name));
        }
        $text = isset($group['title']) ? $this->translator->trans($group['title']) : null;

        return [
            'id' => $name,
            'text' => $text,
            'icon' => $group['icon'] ?? null,
            'parent' => $parentName,
            'priority' => $node['priority'] ?? 0,
            'search_by' => [$text]
        ];
    }

    /**
     * @param string $name
     * @param string $parentName
     * @param array $node
     * @return array
     */
    private function buildJsTreeItemWithSearchData($name, $parentName, array $node)
    {
        $jsTreeData = $this->buildJsTreeItem($name, $parentName, $node);

        if ($this->searchProvider->supports($name)) {
            $children = array_key_exists('children', $node) ? $node['children'] : [];
            $jsTreeData['search_by'] = $this->prepareSearchData($name, $children);
        }

        return $jsTreeData;
    }

    /**
     * @param string $name
     * @param array $itemChildren
     * @return array
     */
    private function prepareSearchData($name, array $itemChildren = [])
    {
        $itemSearchData = $this->searchProvider->getData($name);

        foreach ($itemChildren as $childName => $childData) {
            if (is_array($childData)) {
                $children = array_key_exists('children', $childData) ? $childData['children'] : [];
                $itemSearchData = array_merge($itemSearchData, $this->prepareSearchData($childName, $children));
            } else {
                $itemSearchData = array_merge($itemSearchData, $this->prepareSearchData($childData));
            }
        }

        return $itemSearchData;
    }

    /**
     * Builds field data by name
     *
     * @param string $node field node name
     *
     * @return FieldNodeDefinition
     * @throws ItemNotFoundException
     */
    protected function buildFieldNode($node)
    {
        $fieldsRoot = $this->configBag->getFieldsRoot($node);
        if ($fieldsRoot === false) {
            throw new ItemNotFoundException(sprintf('Field "%s" is not defined.', $node));
        }

        return new FieldNodeDefinition($node, $fieldsRoot);
    }

    protected function buildApiTree(string $sectionName, array $tree): SectionDefinition
    {
        $section = new SectionDefinition($sectionName);
        foreach ($tree as $key => $val) {
            if ($key === 'section') {
                continue;
            }
            if (!empty($val['section'])) {
                $section->addSubSection(
                    $this->buildApiTree($key, $val)
                );
            } else {
                $section->addVariable(
                    new VariableDefinition($key, $val['type'])
                );
            }
        }

        return $section;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataTransformer(string $key): ?DataTransformerInterface
    {
        return $this->configBag->getDataTransformer($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(string $groupName, ConfigManager $configManager): FormInterface
    {
        $toAddFields = [];
        $block = $this->getSubTree($groupName);
        $blockConfig = $block->toBlockConfig();
        if (!$block->isEmpty()) {
            $blockName = $block->getName();
            $subBlockConfig = [];
            /** @var GroupNodeDefinition $subBlock */
            foreach ($block as $subBlock) {
                $subBlockConfig += $subBlock->toBlockConfig();
                if (!$subBlock->isEmpty()) {
                    /** @var FieldNodeDefinition $field */
                    foreach ($subBlock as $field) {
                        if (!$field->getAclResource() || $this->checkIsGranted($field->getAclResource())) {
                            $field->replaceOption('block', $blockName);
                            $field->replaceOption('subblock', $subBlock->getName());
                            $toAddFields[] = $field;
                        }
                    }
                }
            }
            $blockConfig[$blockName]['subblocks'] = $subBlockConfig;
        }

        $formOptions = [];
        foreach ($toAddFields as $field) {
            $formOptions[$field->getPropertyPath()] = $this->getFieldFormOptions($field);
        }

        $event = new ConfigSettingsFormOptionsEvent($configManager, $formOptions);
        $this->eventDispatcher->dispatch($event, ConfigSettingsFormOptionsEvent::SET_OPTIONS);
        $formOptions = $event->getAllFormOptions();

        $formBuilder = $this->formFactory->createNamedBuilder(
            $groupName,
            FormType::class,
            null,
            ['block_config' => $blockConfig]
        );
        foreach ($formOptions as $propertyPath => $options) {
            $formBuilder->add(
                str_replace(
                    ConfigManager::SECTION_MODEL_SEPARATOR,
                    ConfigManager::SECTION_VIEW_SEPARATOR,
                    $propertyPath
                ),
                FormFieldType::class,
                $options
            );
        }

        return $formBuilder->getForm();
    }

    /**
     * {@inheritdoc}
     */
    public function chooseActiveGroups(?string $activeGroup, ?string $activeSubGroup): array
    {
        $tree = $this->getTree();

        if ($activeGroup === null) {
            $activeGroup = TreeUtils::getFirstNodeName($tree);
        }

        // we can find active subgroup only in case if group is specified
        if ($activeSubGroup === null && $activeGroup) {
            $subtree = TreeUtils::findNodeByName($tree, $activeGroup);

            if ($subtree instanceof GroupNodeDefinition) {
                $subGroups = TreeUtils::getByNestingLevel($subtree, 2);

                if ($subGroups instanceof GroupNodeDefinition) {
                    $activeSubGroup = TreeUtils::getFirstNodeName($subGroups);
                }
            }
        }

        return [$activeGroup, $activeSubGroup];
    }

    /**
     * Checks whether an access to a given ACL resource is granted
     *
     * @param string $resourceName
     *
     * @return bool
     */
    protected function checkIsGranted($resourceName)
    {
        return $this->authorizationChecker->isGranted($resourceName);
    }

    protected function getFieldFormOptions(FieldNodeDefinition $fieldDefinition): array
    {
        // take config field options form field definition
        $fieldDefinitionOptions = $fieldDefinition->getOptions();
        $options = array_intersect_key(
            $fieldDefinitionOptions,
            array_flip(['label', 'required', 'block', 'subblock', 'tooltip', 'resettable'])
        );
        // pass only options needed to "value" form type
        $formType = $fieldDefinition->getType();
        $options['target_field_type'] = $formType;
        $options['target_field_alias'] = $this->formRegistry->getType($formType)->getBlockPrefix();
        $options['target_field_options'] = array_diff_key($fieldDefinitionOptions, $options);

        if ($fieldDefinition->needsPageReload()) {
            $options['target_field_options']['attr']['data-needs-page-reload'] = '';
            $options['use_parent_field_options']['attr']['data-needs-page-reload'] = '';
        }
        $options['use_parent_field_label'] = $this->getParentCheckboxLabel();

        return $options;
    }

    private function sortChildrenByPriority(array $children): array
    {
        uasort($children, function ($childA, $childB) {
            $priorityA = $childA['priority'] ?? 0;
            $priorityB = $childB['priority'] ?? 0;

            return $priorityB <=> $priorityA;
        });

        return $children;
    }
}
