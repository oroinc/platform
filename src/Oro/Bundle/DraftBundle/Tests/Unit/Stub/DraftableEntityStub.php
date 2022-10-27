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
     * @var array
     */
    private $values = [];

    /**
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return isset($this->{$name}) ? $this->{$name} : null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name): bool
    {
        return isset($this->{$name});
    }
}
