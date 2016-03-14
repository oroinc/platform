<?php

namespace Oro\Bundle\ActionBundle\Model;

interface EntityAwareInterface
{
    /**
     * @return object
     */
    public function getEntity();
}
