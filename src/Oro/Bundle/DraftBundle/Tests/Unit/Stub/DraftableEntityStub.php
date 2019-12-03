<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Stub;

use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Entity\DraftableTrait;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;

class DraftableEntityStub implements DraftableInterface
{
    use DraftableTrait;
    use DatesAwareTrait;

    /**
     * @var int
     */
    protected $id;

    /**
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }
}
