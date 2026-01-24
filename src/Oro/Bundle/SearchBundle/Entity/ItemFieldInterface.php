<?php

namespace Oro\Bundle\SearchBundle\Entity;

/**
 * Defines the contract for search index item field values.
 *
 * This interface specifies the methods for managing individual field values
 * within a search index item, including getting and setting field names, values,
 * and the parent item reference. Implementations represent typed field values
 * (text, integer, decimal, datetime) stored in the search index.
 */
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
