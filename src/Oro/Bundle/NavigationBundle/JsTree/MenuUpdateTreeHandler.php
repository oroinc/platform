<?php

namespace Oro\Bundle\NavigationBundle\JsTree;

use Knp\Menu\ItemInterface;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Tree\Handler\AbstractTreeHandler;

class MenuUpdateTreeHandler extends AbstractTreeHandler
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    protected function moveProcessing($entityId, $parentId, $position)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createTree($root = null, $includeRoot = true)
    {
        if ($root === null) {
            return [];
        }

        $tree = $this->getNodes($root, $includeRoot);

        return $this->formatTree($tree, $root, $includeRoot);
    }

    /**
     * {@inheritdoc}
     *
     * @param ItemInterface $root
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
     * {@inheritdoc}
     */
    protected function formatTree(array $entities, $root, $includeRoot)
    {
        $formattedTree = [];

        foreach ($entities as $entity) {
            $node = $this->formatEntity($entity);

            if ($entity === $root) {
                $node['parent'] = self::ROOT_PARENT_VALUE;
            }

            $formattedTree[] = $node;
        }

        return $formattedTree;
    }

    /**
     * {@inheritdoc}
     */
    protected function formatEntity($entity)
    {
        return [
            'id' => $entity->getName(),
            'parent' => $entity->getParent() ? $entity->getParent()->getName() : null,
            'text' => $this->translator->trans($entity->getLabel()),
            'state' => [
                'opened' => $entity->getParent() === null,
                'disabled' => !$entity->getExtra('editable', false)
            ]
        ];
    }
}
