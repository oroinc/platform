<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;

class DeleteContext extends SingleItemContext
{
    const OBJECT = 'object';

    /**
     * Sets object needs to be deleted.
     *
     * @param mixed $object
     */
    public function setObject($object)
    {
        $this->set(self::OBJECT, $object);
    }

    /**
     * Gets object needs to be deleted.
     *
     * @return mixed|null
     */
    public function getObject()
    {
        return $this->get(self::OBJECT);
    }

    /**
     * Returns true if object was set.
     *
     * @return bool
     */
    public function hasObject()
    {
        return $this->has(self::OBJECT);
    }

    /**
     * Removes object from context.
     */
    public function removeObject()
    {
        $this->remove(self::OBJECT);
    }
}
