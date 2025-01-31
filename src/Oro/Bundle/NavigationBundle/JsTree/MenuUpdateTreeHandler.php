<?php

namespace Oro\Bundle\NavigationBundle\JsTree;

use Knp\Menu\ItemInterface;
use Oro\Bundle\UIBundle\Model\TreeItem;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Returns a formatted menu updates tree.
 */
class MenuUpdateTreeHandler
{
    const MENU_ITEM_DIVIDER_LABEL = '---------------';
    const ROOT_PARENT_VALUE = '#';

    /**
     * Used to hide inaccessible menu items for back-office menu tree
     */
    public const EXTRA_IS_ALLOWED_FOR_BACKOFFICE = 'isAllowedForBackOffice';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param ItemInterface|null $root
     * @param bool               $includeRoot
     * @return array
     */
    public function createTree(?ItemInterface $root = null, $includeRoot = true)
    {
        if ($root === null) {
            return [];
        }

        $tree = $this->getNodes($root, $includeRoot);

        return $this->formatTree($tree, $root, $includeRoot);
    }

    /**
     * @param ItemInterface $root
     * @param bool          $includeRoot
     * @return array
     */
    protected function getNodes(ItemInterface $root, $includeRoot)
    {
        $nodes = [];
        if ($includeRoot && $root->getExtra(self::EXTRA_IS_ALLOWED_FOR_BACKOFFICE, true) !== false) {
            $nodes[] = [$root];
        }

        foreach ($root->getChildren() as $child) {
            if ($child->getExtra(self::EXTRA_IS_ALLOWED_FOR_BACKOFFICE) === false) {
                continue;
            }

            $nodes[] = [$child];
            $nodes[] = $this->getNodes($child, false);
        }

        return array_merge(...$nodes);
    }

    /**
     * @param array         $entities
     * @param ItemInterface $root
     * @param string        $includeRoot
     * @return array
     */
    protected function formatTree(array $entities, $root, $includeRoot)
    {
        $formattedTree = [];

        foreach ($entities as $entity) {
            $node = $this->formatEntity($entity);

            if ($entity === $root) {
                if ($includeRoot) {
                    $node['parent'] = self::ROOT_PARENT_VALUE;
                } else {
                    continue;
                }
            }

            $formattedTree[] = $node;
        }

        return $formattedTree;
    }

    /**
     * @param ItemInterface $entity
     * @return array
     */
    protected function formatEntity($entity)
    {
        $text = $entity->getLabel();
        if ($entity->getExtra('divider', false)) {
            $text = self::MENU_ITEM_DIVIDER_LABEL;
        } elseif (!$entity->getExtra('translate_disabled', false)) {
            $text = $this->translator->trans((string) $text);
        }

        return [
            'id' => $entity->getName(),
            'parent' => $entity->getParent() ? $entity->getParent()->getName() : null,
            'text' => $text,
            'state' => [
                'opened' => $entity->getParent() === null,
                'disabled' => $entity->getExtra('read_only', false)
            ],
            'li_attr' => !$entity->isDisplayed() ? ['class' => 'hidden'] : []
        ];
    }

    /**
     * @param object|null $root
     * @param bool        $includeRoot
     * @return TreeItem[]
     */
    public function getTreeItemList($root = null, $includeRoot = true)
    {
        $nodes = $this->createTree($root, $includeRoot);

        $items = [];

        foreach ($nodes as $node) {
            $items[$node['id']] = new TreeItem($node['id'], $node['text']);
        }

        foreach ($nodes as $node) {
            if (array_key_exists($node['parent'], $items)) {
                /** @var TreeItem $treeItem */
                $treeItem = $items[$node['id']];
                $treeItem->setParent($items[$node['parent']]);
            }
        }

        return $items;
    }

    /**
     * @param TreeItem[] $sourceData
     * @param array      $treeData
     */
    public function disableTreeItems(array $sourceData, array &$treeData)
    {
        foreach ($treeData as &$treeItem) {
            foreach ($sourceData as $sourceItem) {
                if ($sourceItem->getKey() === $treeItem['id'] || $sourceItem->hasChildRecursive($treeItem['id'])) {
                    $treeItem['state']['disabled'] = true;
                }
            }
        }
    }
}
