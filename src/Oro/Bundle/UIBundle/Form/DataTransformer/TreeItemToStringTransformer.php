<?php

namespace Oro\Bundle\UIBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use Symfony\Component\Form\Exception\TransformationFailedException;
use Oro\Bundle\UIBundle\Model\TreeItem;

class TreeItemToStringTransformer implements DataTransformerInterface
{
    /** @var TreeItem[] */
    private $items;

    /**
     * @param TreeItem[] $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $values = [];
            foreach ($value as $item) {
                $values[] = $this->getItemKey($item);
            }

            return $values;
        }

        return $this->getItemKey($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $items = [];
            foreach ($value as $key) {
                $items[] = $this->findItem($key);
            }

            return $items;
        }

        return $this->findItem($value);
    }

    /**
     * @param string $key
     *
     * @return TreeItem|null
     */
    private function findItem($key)
    {
        foreach ($this->items as $item) {
            if ($item->getKey() === $key) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param mixed $item
     *
     * @return string
     */
    private function getItemKey($item)
    {
        if (!$item instanceof TreeItem) {
            throw new TransformationFailedException(sprintf(
                'Value must be instance of TreeItem or list of TreeItem[], but "%s" is given.',
                is_object($item) ? get_class($item) : gettype($item)
            ));
        }

        return $item->getKey();
    }
}
