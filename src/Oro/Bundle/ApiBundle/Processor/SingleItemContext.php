<?php

namespace Oro\Bundle\ApiBundle\Processor;

/**
 * The base execution context for processors for actions work with one entity,
 * such as "get", "update", "create" and "delete".
 */
class SingleItemContext extends Context
{
    /** @var mixed */
    private $id;

    /**
     * Gets an identifier of an entity
     *
     * @return mixed|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets an identifier of an entity
     *
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
