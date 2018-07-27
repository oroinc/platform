<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;

/**
 * This class is used to testing EntityInstantiator for a model inherited from an entity
 * and when a model has a constructor with required parameters.
 */
class UserProfileWithConstructor extends User
{
    /**
     * @param int $id
     */
    public function __construct($id)
    {
        parent::__construct();
        $this->id = $id;
    }
}
