<?php

namespace Oro\Bundle\UserBundle\Model;

use Symfony\Component\Security\Core\Role\Role;

class ExtendRole extends Role
{
    /**
     * Constructor
     *
     * The real implementation of this method is auto generated.
     *
     * IMPORTANT: If the derived class has own constructor it must call parent constructor.
     *
     * {@inheritdoc}
     */
    public function __construct($role)
    {
        parent::__construct($role);
    }
}
