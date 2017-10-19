<?php

namespace Oro\Bundle\UserBundle\Model;

use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\AbstractRole;

/**
 * @property OrganizationInterface $organization
 */
abstract class ExtendRole extends AbstractRole
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
