<?php

namespace Oro\Bundle\OrganizationBundle\Entity;

interface BusinessUnitInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();
}
