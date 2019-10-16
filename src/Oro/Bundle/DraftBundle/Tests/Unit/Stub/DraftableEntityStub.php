<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Stub;

use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Entity\DraftableTrait;

class DraftableEntityStub implements DraftableInterface
{
    use DraftableTrait;

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
