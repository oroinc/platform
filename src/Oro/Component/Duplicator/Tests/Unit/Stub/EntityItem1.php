<?php

namespace Oro\Component\Duplicator\Tests\Unit\Stub;

use Doctrine\Common\Collections\ArrayCollection;

class EntityItem1
{
    /**
     * @var ArrayCollection
     */
    protected $items;

    /**
     * @var string
     */
    protected $comment;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    /**
     * @param EntityItem2 $item
     */
    public function addItem(EntityItem2 $item)
    {
        $this->items->add($item);
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return ArrayCollection|EntityItem2[]
     */
    public function getItems()
    {
        return $this->items;
    }
}
