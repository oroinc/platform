<?php

namespace Oro\Bundle\SoapBundle\Entity;

interface SoapEntityInterface
{
    /**
     * Init soap entity with original entity
     *
     * @param mixed $entity
     */
    public function soapInit($entity);
}
