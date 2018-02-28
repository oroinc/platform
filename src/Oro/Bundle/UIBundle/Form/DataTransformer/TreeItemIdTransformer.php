<?php

namespace Oro\Bundle\UIBundle\Form\DataTransformer;

use Oro\Bundle\UIBundle\Model\TreeItem;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TreeItemIdTransformer implements DataTransformerInterface
{
    /**
     * @var TreeItem[]
     */
    private $treeItems = [];

    /**
     * @param TreeItem[] $treeItems
     */
    public function __construct(array $treeItems)
    {
        $this->treeItems = $treeItems;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if ($value !== null && !$value instanceof TreeItem) {
            throw new TransformationFailedException("Unsupported value type");
        }

        return $value ? $value->getKey() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if ($value !== null && !isset($this->treeItems[$value])) {
            throw new TransformationFailedException("Item with id = \"$value\" is undefined");
        }

        return $value == null ? $value : $this->treeItems[$value];
    }
}
