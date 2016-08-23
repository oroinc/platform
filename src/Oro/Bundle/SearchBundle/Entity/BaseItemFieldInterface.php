<?php

namespace Oro\Bundle\SearchBundle\Entity;

interface BaseItemFieldInterface
{
    /**
     * @param string $field
     * @return BaseItemFieldInterface
     */
    public function setField($field);

    /**
     * @return string
     */
    public function getField();

    /**
     * @param mixed $value
     * @return BaseItemFieldInterface
     * @throws \InvalidArgumentException
     */
    public function setValue($value);

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @param BaseItem $item
     * @return BaseItemFieldInterface
     */
    public function setItem(BaseItem $item = null);

    /**
     * @return BaseItem
     */
    public function getItem();
}
