<?php

namespace Oro\Bundle\SearchBundle\Entity;

interface ItemFieldInterface
{
    /**
     * @param string $field
     * @return ItemFieldInterface
     */
    public function setField($field);

    /**
     * @return string
     */
    public function getField();

    /**
     * @param mixed $value
     * @return ItemFieldInterface
     * @throws \InvalidArgumentException
     */
    public function setValue($value);

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @param AbstractItem $item
     * @return ItemFieldInterface
     */
    public function setItem(AbstractItem $item);

    /**
     * @return AbstractItem
     */
    public function getItem();
}
