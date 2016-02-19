<?php

namespace Oro\Bundle\ApiBundle\Request;

use Doctrine\Common\Collections\ArrayCollection;

class ApiResourceCollection extends ArrayCollection
{
    /**
     * This array is used to prevent adding duplicates
     *
     * @var array [resource key => true, ...]
     */
    protected $keys;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $elements = [])
    {
        parent::__construct($elements);

        $this->keys = [];
        foreach ($elements as $element) {
            $this->keys[(string)$element] = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add($value)
    {
        $key = (string)$value;
        if (!isset($this->keys[$key])) {
            parent::add($value);
            $this->keys[$key] = true;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        $removedElement = parent::remove($key);
        if (null !== $removedElement) {
            unset($this->keys[(string)$removedElement]);
        }

        return $removedElement;
    }

    /**
     * {@inheritdoc}
     */
    public function removeElement($element)
    {
        $removed = parent::removeElement($element);
        if ($removed) {
            unset($this->keys[(string)$element]);
        }

        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $existingElement = $this->get($key);
        if (null !== $existingElement) {
            unset($this->keys[(string)$existingElement]);
        }
        $resKey = (string)$value;
        if (isset($this->keys[$resKey])) {
            $elements = $this->toArray();
            foreach ($elements as $elementKey => $element) {
                if ($resKey === (string)$element) {
                    $this->remove($elementKey);
                    break;
                }
            }
        }

        parent::set($key, $value);
        $this->keys[$resKey] = true;
    }
}
