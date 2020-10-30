<?php

namespace Oro\Bundle\GoogleIntegrationBundle\Tests\Unit\Stub;

use Oro\Bundle\UserBundle\Entity\User as BaseUser;

class User extends BaseUser
{
    /** @var string|null */
    private $googleId;

    /**
     * The getter for googleId field that is added to User entity as an expended field.
     *
     * @return string|null
     */
    public function getGoogleId()
    {
        return $this->googleId;
    }

    /**
     * The setter for googleId field that is added to User entity as an expended field.
     *
     * @param string|null $id
     */
    public function setGoogleId($id)
    {
        $this->googleId = $id;
    }
}
