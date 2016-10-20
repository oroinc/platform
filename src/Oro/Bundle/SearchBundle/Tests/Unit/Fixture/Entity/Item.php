<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity;

use Oro\Bundle\SearchBundle\Entity\Item as BaseItem;

class Item extends BaseItem
{
    /**
     * @param int $id
     *
     * @return Item
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
