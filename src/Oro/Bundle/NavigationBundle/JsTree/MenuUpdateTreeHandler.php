<?php

namespace Oro\Bundle\NavigationBundle\JsTree;

use Knp\Menu\ItemInterface;

use Symfony\Component\Translation\TranslatorInterface;

class MenuUpdateTreeHandler
{
    const MENU_ITEM_DIVIDER_LABEL = '---------------';
    const ROOT_PARENT_VALUE = '#';

    /**
     * @var TranslatorInterface
     */
    protected $translator;


    /**
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function createTree(ItemInterface $root = null, $includeRoot = true)
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
        if ($includeRoot) {
            $nodes[] = $root;
        }

        /** @var ItemInterface $child */
        foreach ($root->getChildren() as $child) {
            $nodes[] = $child;
            $nodes = array_merge($nodes, $this->getNodes($child, false));
        }

        return $nodes;
    }

    /**
     * @param array          $entities
     * @param  ItemInterface $root
     * @param  string        $includeRoot
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
     */
    protected function formatEntity($entity)
    {
        $isDivider = $entity->getExtra('divider', false);
        // todo consider to remove translator from here
        $text = $isDivider ? self::MENU_ITEM_DIVIDER_LABEL : $this->translator->trans($entity->getLabel());

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
}
