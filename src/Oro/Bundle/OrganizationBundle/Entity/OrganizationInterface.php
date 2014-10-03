<?php

namespace Oro\Bundle\OrganizationBundle\Entity;

interface OrganizationInterface
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
