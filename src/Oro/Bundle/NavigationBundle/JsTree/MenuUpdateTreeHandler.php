<?php

namespace Oro\Bundle\NavigationBundle\JsTree;

use Doctrine\Common\Persistence\ManagerRegistry;
use Knp\Menu\ItemInterface;

use Symfony\Component\Translation\TranslatorInterface;

class MenuUpdateTreeHandler
{
    const MENU_ITEM_DIVIDER_LABEL = '---------------';
    const ROOT_PARENT_VALUE = '#';

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var TranslatorInterface
     */
    protected $translator;


    /**
     * @param string          $entityClass
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct($entityClass, ManagerRegistry $managerRegistry, TranslatorInterface $translator)
    {
        $this->entityClass = $entityClass;
        $this->managerRegistry = $managerRegistry;
        $this->translator = $translator;
    }

    public function createTree($root = null, $includeRoot = true)
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
    protected function getNodes($root, $includeRoot)
    {
        $nodes = [];
        if ($includeRoot) {
            $nodes[] = $root;
        }

        /** @var ItemInterface $child */
        foreach ($root->getChildren() as $child) {
            if ($child->isDisplayed()) {
                $nodes[] = $child;
                $nodes = array_merge($nodes, $this->getNodes($child, false));
            }
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
        $text = $isDivider ? self::MENU_ITEM_DIVIDER_LABEL : $this->translator->trans($entity->getLabel());

        return [
            'id' => $entity->getName(),
            'parent' => $entity->getParent() ? $entity->getParent()->getName() : null,
            'text' => $text,
            'state' => [
                'opened' => $entity->getParent() === null,
                'disabled' => !$entity->getExtra('editable', false)
            ]
        ];
    }
}
